<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps WordPress terms to their updated values.
 *
 * @MigrateProcessPlugin(
 *   id = "term_map"
 * )
 */
class TermMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->mapTermId($value);
  }

  /**
   * Determines if a term should be imported.
   *
   * @param int $term_id
   *   A source term ID.
   *
   * @return bool
   *   Boolean indicating if the term should be imported.
   */
  public static function importTerm($term_id) {
    $map = self::importTermMap();
    if (array_key_exists($term_id, $map)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Remaps source term ID values.
   *
   * @param int $term_id
   *   The source term ID.
   * @return int|bool
   *   The re-mapped term ID or the original value if no remapping occured.
   */
  protected function mapTermId($term_id) {
    $map = $this->importTermMap();
    if (array_key_exists($term_id, $map)) {
      if (!empty($map[$term_id])) {
        return $map[$term_id];
      }
      return $term_id;
    }
    return NULL;
  }

  /**
   * Imports the CSV term map.
   *
   * @return array
   *   An array keyed by source term ID.
   */
  protected static function importTermMap() {
    static $map = [];
    if (!empty($map)) {
      return $map;
    }
    $data = array_map('str_getcsv', file(drupal_get_path('module', 'example_wordpressD8_migrate') . '/data/terms.csv'));
    array_shift($data);
    foreach ($data as $row) {
      $map[$row[0]] = !empty($row[12]) ? $row[12] : NULL;
    }
    return $map;
  }

}
