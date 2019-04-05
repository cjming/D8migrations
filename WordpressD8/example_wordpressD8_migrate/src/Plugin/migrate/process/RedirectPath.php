<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Reformats redirects to internal paths.
 *
 * @MigrateProcessPlugin(
 *   id = "redirect_path"
 * )
 *
 * example input - /fresh-pasta/
 * example output - fresh-pasta
 */
class RedirectPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return ltrim(parse_url($value, PHP_URL_PATH), '/');
  }

}
