<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Row;
use Drupal\redirect\Plugin\migrate\source\d7\PathRedirect;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *  id = "redirects"
 * )
 */
class Redirects extends PathRedirect {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')->fields('p')
      ->condition('redirect', '%user%', 'NOT LIKE');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the current status code and set it.
    $current_status_code = $row->getSourceProperty('status_code');
    $status_code = $current_status_code != 0 ? $current_status_code : 301;
    $row->setSourceProperty('status_code', $status_code);
    // Get the redirect to see if it's a node.
    $current_redirect = $row->getSourceProperty('redirect');

    $explode_current_redirect = explode("/", $current_redirect);
    // Content types whose redirects we want to migrate.
    $map_types_array = [
      'course',
      'event',
      'page',
      'person',
      'project',
      'publication',
      'article',
    ];
    // Determine if the path is redirected to a /node/{id} path.
    if ($explode_current_redirect[0] == 'node') {
      // Determine the content type for the node.
      $resource_type = $this->getDatabase()
        ->select('node', 'n')
        ->fields('n', ['type'])
        ->condition('nid', $explode_current_redirect[1])
        ->execute()
        ->fetchField();

      // Check that the type is in the node types we want to migrate for.
      if (in_array($resource_type, $map_types_array)) {
        $this_type = !empty($map_types_tables[$resource_type]) ? $map_types_tables[$resource_type] : $resource_type . 's';
        // Gather the information about where the node is now.
        $new_nid = Database::getConnection('default', 'default')
          ->select('migrate_map_' . $this_type, 'm')
          ->fields('m', ['destid1'])
          ->condition('sourceid1', $explode_current_redirect[1])
          ->execute()
          ->fetchField();

        // Set the new redirect.
        if (!empty($new_nid)) {
          $new_redirect = 'node/' . $new_nid;
          $row->setSourceProperty('redirect', $new_redirect);
        }
        else {
          // This node does not exist.
          return FALSE;
        }
      }
      else {
        // This node is not one of the types we are migrating.
        return FALSE;
      }
    }
    else {
      // Check if the path exists.
      $is_valid = \Drupal::service('path.validator')->isValid($current_redirect);
      if (!$is_valid) {
        // The redirect path does not exist.
        return FALSE;
      }
    }
  }

}
