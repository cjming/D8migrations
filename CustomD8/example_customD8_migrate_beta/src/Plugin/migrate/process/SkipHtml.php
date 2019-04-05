<?php

namespace Drupal\example_customD8_migrate_beta\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skip row if wrong file extension.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_html"
 * )
 */
class SkipHtml extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    // Extract the file extension.
    $file_parts = pathinfo($value);
    $extension = strtolower($file_parts['extension']);

    if ($extension == 'shtml' || empty($extension)) {
      throw new MigrateSkipRowException();
    }
    return $value;
  }

}
