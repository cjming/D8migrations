<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Import a file as a side-effect of a migration.
 *
 * @MigrateProcessPlugin(
 *   id = "file_name"
 * )
 */
class FileName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    // Extract the file name.
    $file_parts = pathinfo($value);
    return $file_parts['basename'];
  }

}
