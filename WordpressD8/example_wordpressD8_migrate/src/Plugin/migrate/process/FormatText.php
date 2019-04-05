<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Formats text using the Full HTML input format.
 *
 * @MigrateProcessPlugin(
 *   id = "format_text"
 * )
 */
class FormatText extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = check_markup($value, 'filtered_html');
    return $value;
  }

}
