<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skip row if wrong file extension.
 *
 * @MigrateProcessPlugin(
 *   id = "check_for_web"
 * )
 */
class CheckForWeb extends ProcessPluginBase {

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

    $permitted_extensions = [
      'shtml',
      'dhtml',
      'html',
      'htm',
    ];

    if (!in_array($extension, $permitted_extensions)) {
      throw new MigrateSkipRowException();
    }
    return $value;
  }

}
