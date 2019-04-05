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
 *   id = "alpha_protected"
 * )
 */
class AlphaProtected extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if empty.
      throw new MigrateSkipRowException();
    }
    // Check if filepath contains member or mem, it is protected content.
    $contains_member = strpos($value, '/member/');
    $contains_mem = strpos($value, '/mem/');

    // Set the stream wrapper to public or private based on string in file path.
    $protected = ($contains_member === FALSE && $contains_mem === FALSE) ? 0 : 1;

    return $protected;
  }

}
