## Synopsis

QAShot is a Drupal 8.2 module which provides a UI to create BackstopJS configuration files.
The user can also run these tests manually or schedule them to run at given times.

## Installation

The UI part is standard Drupal installation. Dependencies are managed with composer.

To run the tests, you need [BackstopJS 2.0](https://github.com/garris/BackstopJS "BackstopJS Repository") installed globally. 

For installation inside an amazee.io container refer to this manual:
https://docs.google.com/document/d/1GUmDacF-VSw-e1HvzOzaq_Su_Cz0FszPX-_8dr53tnw/edit , then

composer install
drush site-install --db-url=mysql://$AMAZEEIO_DB_USERNAME:$AMAZEEIO_DB_PASSWORD@localhost/drupal
npm install -g backstopjs
