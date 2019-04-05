<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Reformats redirects to internal paths.
 *
 * @MigrateProcessPlugin(
 *   id = "redirect_internal"
 * )
 *
 * example input - http://www.wordpressD8.com/things-in-the-wild/
 * example output - internal:/things-in-the-wild
 */
class RedirectInternal extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = ltrim(str_replace('http://www.wordpressD8.com/', '', $value), '/');
    $value = str_replace('httpwordpress-D8-com', '', $value);
    if (strpos($value, 'http') !== 0) {
      $value = 'internal:/' . $value;
    }
    return $value;
  }

}
