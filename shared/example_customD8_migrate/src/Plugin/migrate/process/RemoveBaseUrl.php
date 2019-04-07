<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Remove the base url from a path.
 *
 * @MigrateProcessPlugin(
 *   id = "remove_base_url"
 * )
 */
class RemoveBaseUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    // If the base url isn't available, throw error.
    if (empty($base_url = $this->configuration['baseurl'])) {
      throw new RequirementsException('No base url supplied.');
    }
    // Remove the base url from the string.
    return str_replace($base_url, '', $value);
  }

}
