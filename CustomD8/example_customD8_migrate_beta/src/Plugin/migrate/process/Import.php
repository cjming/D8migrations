<?php

namespace Drupal\example_customD8_migrate_beta\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skip row if indicated.
 *
 * @MigrateProcessPlugin(
 *   id = "import"
 * )
 */
class Import extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no value in keep column.
      throw new MigrateSkipRowException();
    }
    $keep = strtolower($value);
    if ($keep == 'no') {
      throw new MigrateSkipRowException();
    }
    return $value;
  }

}
