<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Unicode;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Load xml and import values into specified fields.
 *
 * @MigrateProcessPlugin(
 *   id = "parse_xml"
 * )
 */
class ParseXml extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    $matched_field = '';
    // If the module name is N/A, throw error.
    if (empty($module = $this->configuration['module'])) {
      throw new RequirementsException('No module name supplied.');
    }
    // Set the relative page to the DCR/XML.
    $xml_path_and_name = 'modules/custom/' . $module . '/data/xml/' . $value;

    // We check for and load the xml file.
    if (file_exists($xml_path_and_name)) {
      $xml = simplexml_load_file($xml_path_and_name);
      // This plugin deals with multiple fields, check which one and process.
      switch ($destination_property) {
        case 'title':
          $matched_field = $xml->page_title;
          break;

        case 'field_metatag_description':
          $meta_description = $xml->page_metadata->meta_description;
          $matched_field = $this->trimText($meta_description, 150);
          break;

      }
    }
    else {
      throw new MigrateSkipRowException();
    }
    return $matched_field;
  }

  /**
   * Trim text.
   *
   * @param string $string
   *   A string of text.
   * @param int $max_length
   *   An integer for max length of string.
   * @param int $min_length
   *   An integer for minimum length of string.
   *
   * @return string
   *   A string of text trimmed on a word boundary with ellipses.
   */
  protected function trimText($string, $max_length, $min_length = 108) {
    $string = trim($string);
    return Unicode::truncate($string, $max_length, TRUE, TRUE, $min_length);
  }

}
