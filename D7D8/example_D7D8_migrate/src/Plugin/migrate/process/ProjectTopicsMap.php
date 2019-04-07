<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps Project names to their Topics term IDs.
 *
 * @MigrateProcessPlugin(
 *   id = "project_topics_map"
 * )
 */
class ProjectTopicsMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->mapTopicsId($value);
  }

  /**
   * Remaps source project names to topic term IDs.
   *
   * @param string $project_name
   *   The source project name.
   *
   * @return array
   *   An array of terms IDs related to a project.
   */
  protected function mapTopicsId($project_name) {
    $map = $this->importProjectTopicsMap();
    if (array_key_exists($project_name, $map)) {
      if (!empty($map[$project_name])) {
        return explode(',', $map[$project_name]);
      }
    }
    return NULL;
  }

  /**
   * Imports the CSV project topics map.
   *
   * @return array
   *   An array keyed by project name.
   */
  protected static function importProjectTopicsMap() {
    static $map = [];
    if (!empty($map)) {
      return $map;
    }
    $data = array_map('str_getcsv', file(drupal_get_path('module', 'example_D7D8_migrate') . '/data/project_topics.csv'));
    array_shift($data);
    foreach ($data as $row) {
      $map[$row[0]] = !empty($row[1]) ? $row[1] : NULL;
    }
    return $map;
  }

}
