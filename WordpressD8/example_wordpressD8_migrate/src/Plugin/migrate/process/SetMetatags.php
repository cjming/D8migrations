<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Sets metatag for node.
 *
 * @MigrateProcessPlugin(
 *   id = "set_metatags"
 * )
 */
class SetMetatags extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $metatags = unserialize($value);
    // Clean up title and description.
    $metatags = [
      'title' => str_replace(' - WordpressD8', '', $metatags['title']) . ' | [site:name]',
      'description' => $this->cleanDescription($metatags['description']),
    ];
    return serialize($metatags);
  }

  /**
   * Strips string to trimmed plain text.
   *
   * @param string $string
   *   A string.
   *
   * @return string
   *   Clean string.
   */
  protected function cleanDescription($string) {
    // Value is likely body copy that needs major cleanup.
    if (strlen($string) > 200) {
      $string = strip_tags($string);
      $string = preg_replace("/\[caption id=(.*?)\[\/caption\]/", '', $string);
      $string = str_replace(array("\r", "\n"), '', $string);
      $string = text_summary($string, 'plain_text', 160);
      $string = preg_replace("/\.[^.]*$/", '.', $string);
    }
    else {
      // Value is likely WP meta tag description field.
      $string = text_summary($string, 'plain_text', strlen($string));
    }
    return $string;
  }
}
