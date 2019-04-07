<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Parse the XML body content.
 *
 * @MigrateProcessPlugin(
 *   id = "create_alias"
 * )
 */
class CreateAlias extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    // If the module name is N/A, throw error.
    if (empty($module = $this->configuration['module'])) {
      throw new RequirementsException('No module name supplied.');
    }

    $xml_path_and_name = 'modules/custom/' . $module . '/data/xml/' . $value;
    // // We check for and load the xml file.
    if (file_exists($xml_path_and_name)) {
      $xml = simplexml_load_file($xml_path_and_name);
      // Use pathauto service to make alias out of page title.
      $alias = \Drupal::service('pathauto.alias_cleaner')->cleanString($xml->page_title);
      return \Drupal::service('pathauto.alias_cleaner')->cleanAlias($alias);
    }
  }

}
