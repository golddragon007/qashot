<?php

namespace Drupal\backstopjs\Component;

use Drupal\backstopjs\Exception\BackstopAlreadyRunningException;
use Drupal\backstopjs\Exception\FileOpenException;
use Drupal\backstopjs\Exception\FileWriteException;
use Drupal\backstopjs\Exception\FolderCreateException;
use Drupal\qa_shot\Entity\QAShotTestInterface;
use Symfony\Component\Process\Process;

/**
 * Class LocalBackstopJS.
 *
 * Implements running BackstopJS from a local binary.
 *
 * @package Drupal\backstopjs\Component
 */
class LocalBackstopJS extends BackstopJSBase {

  const COMMAND_CHECK_STATUS = 'pgrep -f backstop -c';
  const COMMAND_GET_STATUS = 'pgrep -l -a -f backstop';

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    $checkerCommand = escapeshellcmd(self::COMMAND_GET_STATUS);
    // @todo: Refactor and use \Symfony\Component\Process\Process.
    exec($checkerCommand, $execOutput, $status);

    $result = array_filter($execOutput, function ($row) use ($checkerCommand) {
      return strpos($row, $checkerCommand) === FALSE;
    });

    return json_encode(['output' => $result, 'status' => $status]);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRunStatus() {
    $checkerCommand = escapeshellcmd(self::COMMAND_CHECK_STATUS);
    // @todo: Refactor and use \Symfony\Component\Process\Process.
    $res = exec($checkerCommand, $execOutput, $status);

    // > 1 is used since the pgrep command gets included as well.
    if (is_numeric($res) && (int) $res > 1) {
      $this->logger->warning('BackstopJS is already running.');
      throw new BackstopAlreadyRunningException('BackstopJS is already running.');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\backstopjs\Exception\FileWriteException
   * @throws \Drupal\backstopjs\Exception\FolderCreateException
   * @throws \Drupal\backstopjs\Exception\FileOpenException
   */
  public function run(string $engine, string $command, QAShotTestInterface $entity): array {
    // @todo: Add an admin form where the user can input the path of binaries.
    // @todo: What if local install, not docker/server?
    // With slimerjs we have to use xvfb-run.
    $xvfb = '';
    if ($engine === 'slimerjs') {
      $xvfb = 'xvfb-run -a ';
    }

    $backstopCommand = escapeshellcmd($xvfb . 'backstop ' . $command . ' --configPath=' . $entity->getConfigurationPath());
    /** @var array $execOutput */
    /** @var int $status */

    // @todo: Refactor and use \Symfony\Component\Process\Process.
    // @see: http://symfony.com/doc/2.8/components/process.html
    // @see: QAS-10
    exec($backstopCommand, $execOutput, $status);

    $results = [
      'result' => TRUE,
      'passedTestCount' => NULL,
      'failedTestCount' => NULL,
      'bitmapGenerationSuccess' => FALSE,
      'backstopEngine' => $engine,
    ];

    foreach ($execOutput as $line) {
      // Search for bitmap generation string.
      if (strpos($line, 'Bitmap file generation completed.') !== FALSE) {
        $results['bitmapGenerationSuccess'] = TRUE;
        continue;
      }

      // Search for the reports.
      if (strpos($line, 'report |') !== FALSE) {
        // Search for the number of passed tests.
        if ($results['passedTestCount'] === NULL && strpos($line, 'Passed') !== FALSE) {
          $results['passedTestCount'] = (int) preg_replace('/\D/', '', $line);
        }

        // Search for the number of failed tests.
        if ($results['failedTestCount'] === NULL && strpos($line, 'Failed') !== FALSE) {
          $results['failedTestCount'] = (int) preg_replace('/\D/', '', $line);
        }
      }
    }

    try {
      $debugPath = $this->backstopFileSystem->getPrivateFiles() . '/' . $entity->id() . '/debug';
      $debugFile = time() . '.debug.txt';
      $this->backstopFileSystem->createFolder($debugPath);
      $this->backstopFileSystem->createConfigFile($debugPath . '/' . $debugFile, var_export($execOutput, TRUE));
    }
    catch (\Exception $e) {
      $log = $e instanceof FolderCreateException || $e instanceof FileWriteException || $e instanceof FileOpenException;
      if ($log) {
        $this->logger->debug('Could not create debug files for entity ' . $entity->id() . '.');
      }
      else {
        throw $e;
      }

    }

    if (!$results['bitmapGenerationSuccess']) {
      $results['result'] = FALSE;
      drupal_set_message('Bitmap generation failed.');
      return $results;
    }

    /*
    if ($status !== 0) {
    // @todo: Here what?
    }
     */

    return $results;
  }

}