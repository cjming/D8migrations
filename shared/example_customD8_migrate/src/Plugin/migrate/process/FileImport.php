<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Exception\RequirementsException;
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
    // If the destination folder isn't available, throw error.
    if (empty($folder = $this->configuration['folder'])) {
      throw new RequirementsException('No destination folder supplied.');
    }
    // If the destination folder can't be created, throw error.
    $create_dir = file_prepare_directory($folder, FILE_CREATE_DIRECTORY);
    if (!$create_dir) {
      throw new RequirementsException('Destination folder could not be created.');
    }

    // Save the file, return its ID.
    $file = system_retrieve_file($value, $folder, TRUE, FILE_EXISTS_REPLACE);
    if (!$file) {
      // Skip this item if saving the file fails.
      throw new MigrateSkipProcessException();
    }
    return $file->id();
  }

}
