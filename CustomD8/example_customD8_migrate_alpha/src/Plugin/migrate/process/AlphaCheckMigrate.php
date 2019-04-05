<?php

namespace Drupal\example_customD8_migrate_alpha\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Check if row should be migrated per source column.
 *
 * @MigrateProcessPlugin(
 *   id = "alpha_check_migrate"
 * )
 */
class AlphaCheckMigrate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $keep = strtolower($value);
    $keep = trim($keep);
    if (empty($keep) || $keep == 'no') {
      // Skip this item if indicated.
      throw new MigrateSkipRowException();
    }
  }

}
