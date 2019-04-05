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
 *   id = "gamma_parse_body"
 * )
 */
class GammaParseBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }
    $body = str_replace('<![CDATA[', '', $value);
    $body = str_replace(']]>', '', $body);
    return $body;
  }

}
