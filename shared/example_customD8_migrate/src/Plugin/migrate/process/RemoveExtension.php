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
 *   id = "remove_extension"
 * )
 */
class RemoveExtension extends ProcessPluginBase {

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
    $extension = '.' . $file_parts['extension'];
    // Let's strip out the "index" from files a la "index.shtml".
    if (strtolower($file_parts['filename']) == 'index') {
      $extension = 'index' . $extension;
    }
    // Return the file path minus "index" and minus the extension.
    return str_replace($extension, '', strtolower($value));

  }

}
