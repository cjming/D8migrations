<?php

namespace Drupal\example_customD8_migrate_beta\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Apply parent taxonomy term id for type.
 *
 * @MigrateProcessPlugin(
 *   id = "beta_types"
 * )
 */
class BetaTypes extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $type = NULL;
    // Get the mapped parent taxonomy term id.
    $terms = $this->getTypes();
    $trimmed_value = trim($value);
    if (array_key_exists($trimmed_value, $terms)) {
      $type = $terms[$trimmed_value];
    }
    return $type;
  }

  /**
   * Get array of mapped types.
   *
   * @return array
   *   An array of terms and tids.
   */
  protected function getTypes() {
    $types = [
      'Discussion Questions & Templates' => 237,
      'Beta Tools' => 230,
    ];
    return $types;
  }

}
