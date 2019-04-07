<?php

namespace Drupal\example_customD8_migrate\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Check for base url and add if missing.
 *
 * @MigrateProcessPlugin(
 *   id = "check_base_url"
 * )
 */
class CheckBaseUrl extends ProcessPluginBase {

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

    $parsed_url = parse_url($value);
    if (!empty($parsed_url['host']) && !empty($parsed_url['scheme'])) {
      return $value;
    }
    else {
      // Prepend the base url to the relative path.
      return $base_url . $value;
    }
  }

}
