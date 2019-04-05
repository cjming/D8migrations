<?php

namespace Drupal\example_customD8_migrate_gamma\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Parse Gamma's XML body content.
 *
 * @MigrateProcessPlugin(
 *   id = "gamma_parse_title"
 * )
 */
class GammaParseTitle extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    $title = str_replace("'", "", $value);
    $title = str_replace('[print]', '', $title);
    $title = str_replace('[web]', '', $title);
    $title = trim($title);
    $title = strip_tags($title);
    return $title;
  }

}
