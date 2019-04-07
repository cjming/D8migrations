<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Remove file extension.
 *
 * @MigrateProcessPlugin(
 *   id = "strip_leading_slash"
 * )
 */
class StripLeadingSlash extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    // Use ltrim to get rid of any forward slash at beginning of path.
    return ltrim($value, '/');

  }

}
