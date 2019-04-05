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
 *   id = "alpha_destination"
 * )
 */
class AlphaDestination extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if empty.
      throw new MigrateSkipRowException();
    }
    // If filepath contains member or mem, it is protected content.
    $contains_mem = strpos($value, '/mem/');
    $contains_member = strpos($value, '/member/');

    // Set the stream wrapper to public or private based on string in file path.
    $streamwrapper = ($contains_mem === FALSE && $contains_member === FALSE) ? 'public://alpha' : 'private://alpha';

    return $streamwrapper;
  }

}
