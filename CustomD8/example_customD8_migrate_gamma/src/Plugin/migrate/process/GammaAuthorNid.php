<?php

namespace Drupal\example_customD8_migrate_gamma\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Check if author exists, return an NID if exists otherwise create.
 *
 * Not to be confused with a User, the Author is a People node.
 *
 * @MigrateProcessPlugin(
 *   id = "gamma_author_nid"
 * )
 */
class GammaAuthorNid extends ProcessPluginBase {

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || (is_string($value) && empty(trim($value)))) {
      // Skip this item if there is no file path.
      throw new MigrateSkipRowException();
    }

    // Search using the concatenated full name on the node title.
    $query = 'SELECT * FROM {node_field_data} WHERE title = :full_name AND type = :type  LIMIT 1';
    $args = [':full_name' => $value, ':type' => 'people'];

    $row = Database::getConnection('default')
      ->query($query, $args)->fetchObject();

    // Skip if we passed the 'skip_on_match' variable and have a match.
    if (!empty($this->configuration['skip_on_match']) && !empty($row->nid)) {
      throw new MigrateSkipRowException();
    }

    return !empty($row->nid) ? $row->nid : FALSE;
  }

}
