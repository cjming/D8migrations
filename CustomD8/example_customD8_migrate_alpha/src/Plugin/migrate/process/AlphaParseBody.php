<?php

namespace Drupal\example_customD8_migrate_alpha\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Parse Alpha's XML body content.
 *
 * @MigrateProcessPlugin(
 *   id = "alpha_parse_body"
 * )
 */
class AlphaParseBody extends ProcessPluginBase {

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

      // Loop through xml to grab the first main paragraph.
      foreach ($xml->children() as $items) {
        if ($items['name'] == 'MainParagraph1') {
          $content = $items->value;
          break;
        }
      }
      // Loop through the content tags to grab the body text.
      if (!empty($content)) {
        foreach ($content->children() as $content_parts) {
          if ($content_parts['name'] == 'MainText1') {
            $body = $content_parts->value;
            break;
          }
        }
      }
    }
    else {
      throw new MigrateSkipRowException();
    }
    return $body;
  }

}
