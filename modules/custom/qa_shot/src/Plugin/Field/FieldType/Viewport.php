<?php

namespace Drupal\qa_shot\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'qa_shot_viewport' field type.
 *
 * @FieldType(
 *   id = "qa_shot_viewport",
 *   label = @Translation("Viewport"),
 *   description = @Translation("Viewport type for QAShot tests."),
 *   default_widget = "qa_shot_viewport",
 *   default_formatter = "qa_shot_viewport"
 * )
 */
class Viewport extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_name_length' => 40,
      'min_width' => 480,
      'max_width' => 3840,
      'min_height' => 320,
      'max_height' => 2400,
    ] + parent::defaultStorageSettings();
    // @note: Min resolution with default values: 480*320   | 3:2      | HVGA
    // @note: Max resolution with default values: 3840*2400 | 16:10 4k | WQUXGA
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(
    FieldStorageDefinitionInterface $field_definition
  ) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->addConstraint(
        'Length',
        [
          'max' => $field_definition->getSetting('max_name_length'),
        ]
      )
      ->setRequired(TRUE);

    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Width'))
      ->addConstraint(
        'Range',
        [
          'min' => $field_definition->getSetting('min_width'),
          'max' => $field_definition->getSetting('max_width'),
        ]
      )
      ->setRequired(TRUE);

    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Height'))
      ->addConstraint(
        'Range',
        [
          'min' => $field_definition->getSetting('min_height'),
          'max' => $field_definition->getSetting('max_height'),
        ]
      )
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(
    FieldStorageDefinitionInterface $field_definition
  ) {
    $schema = [
      'columns' => [
        'name' => [
          'description' => "Name of the Viewport.",
          'type' => 'varchar',
          'length' => $field_definition->getSetting('max_name_length'),
          'not null' => TRUE,
        ],
        'width' => [
          'description' => "Width of the Viewport.",
          'type' => 'int',
          'size' => 'small',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'height' => [
          'description' => "Height of the Viewport.",
          'type' => 'int',
          'size' => 'small',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
    ];

    // Unsigned small int can hold values between 0 and 65535
    // For screen resolutions that's more than enough future proofing.
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $name = $this->get('name')->getValue();
    $width = $this->get('width')->getValue();
    $height = $this->get('height')->getValue();

    return $name === NULL || $name === '' ||
           $width === NULL || $width === '' ||
           $height === NULL || $height === '';
  }

}
