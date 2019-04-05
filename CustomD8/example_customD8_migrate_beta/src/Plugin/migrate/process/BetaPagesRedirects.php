<?php

namespace Drupal\example_customD8_migrate_beta\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Beta redirects using beta_pages migration.
 *
 * @MigrateProcessPlugin(
 *   id = "beta_pages_redirects"
 * )
 */
class BetaPagesRedirects extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!empty($value)) {
      // Extract the file extension.
      $file_parts = pathinfo($value);
      // We only want to work with the `.shtml` paths, e.g. not `pdf` paths.
      if (strtolower($file_parts['extension']) == 'shtml') {
        // We can get the node id by matching filepath with sourceid1 in
        // `migrate_map_beta_pages`, created by `beta_pages` migration.
        $connection = Database::getConnection('default', 'default');
        // Get the nid.
        $nid = $connection->select('migrate_map_beta_pages', 'tp')
          ->fields('tp', ['destid1'])
          ->condition('tp.sourceid1', trim($row->getSourceProperty('filepath')), '=')
          ->execute()
          ->fetchField();
        // We should have a valid nid here.
        if (is_numeric($nid)) {
          // This format will work in the redirect table.
          return 'internal:/node/' . $nid;
        }
      }
      else {
        throw new MigrateSkipRowException();
      }
    }
  }

}
