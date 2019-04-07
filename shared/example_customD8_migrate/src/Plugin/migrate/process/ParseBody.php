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
 *   id = "parse_body"
 * )
 */
class ParseBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    $body = '';
    // If the module name is N/A, throw error.
    if (empty($module = $this->configuration['module'])) {
      throw new RequirementsException('No module name supplied.');
    }
    // Set the relative page to the DCR/XML.
    $xml_path_and_name = 'modules/custom/' . $module . '/data/xml/' . $value;
    // We check for and load the xml file.
    if (file_exists($xml_path_and_name)) {
      $xml = simplexml_load_file($xml_path_and_name);
      // This structure works with trustees.
      $body = $xml->body_content->text;
    }
    else {
      throw new MigrateSkipRowException();
    }
    return $body;
  }

}
