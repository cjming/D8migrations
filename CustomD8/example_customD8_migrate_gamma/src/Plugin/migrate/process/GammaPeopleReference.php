<?php

namespace Drupal\example_customD8_migrate_gamma\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns an array of matching people NIDs by concat title.
 *
 * @MigrateProcessPlugin(
 *   id = "gamma_people_reference"
 * )
 */
class GammaPeopleReference extends ProcessPluginBase {

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || (is_string($value) && empty(trim($value)))) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }

    $people_nids = [];

    // Split the string, search each individually and get the nid.
    foreach (explode(', ', $value) as $person) {
      $query = 'SELECT * FROM {node_field_data} WHERE title = :full_name AND type = :type  LIMIT 1';
      $args = [':full_name' => trim($person), ':type' => 'people'];
      $row = Database::getConnection('default')
        ->query($query, $args)->fetchObject();
      if (!empty($row->nid)) {
        $people_nids[] = $row->nid;
      }
    }

    return $people_nids;
  }

}
