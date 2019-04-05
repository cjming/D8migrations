<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Import a file as a side-effect of a migration.
 *
 * @MigrateProcessPlugin(
 *   id = "file_import"
 * )
 */
class FileImport extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no URL.
      throw new MigrateSkipRowException();
    }

    // Save the file, return its ID.
    $file = system_retrieve_file($value, 'public://', TRUE, FILE_EXISTS_REPLACE);
    if (!$file) {
      // Skip this item if saving the file fails.
      throw new MigrateSkipProcessException();
    }
    return $file->id();
  }
}
