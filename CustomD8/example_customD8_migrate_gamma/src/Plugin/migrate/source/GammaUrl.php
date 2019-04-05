<?php

namespace Drupal\example_customD8_migrate_gamma\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\taxonomy\Entity\Term;

/**
 * Modified version of URL source to preprocess taxonomy terms.
 *
 * @MigrateSource(
 *   id = "gamma_url"
 * )
 */
class GammaUrl extends Url {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // We need the location of the CSV map file
    // i.e. public://gamma/data/xyz.csv.
    if (!isset($this->configuration['file_stream']['type'])
      &&!isset($this->configuration['file_stream']['topic'])) {
      throw new RequirementsException('File Stream missing');
    }
    elseif (!file_exists($this->configuration['file_stream']['type'])
      &&!file_exists($this->configuration['file_stream']['topic'])) {
      throw new RequirementsException('File stream file location does not exist.');
    }

    // Gather the topic csv rows.
    $topic_tids = [];
    $csv_file = fopen($this->configuration['file_stream']['topic'], 'r');
    $topic_map = [];
    // Loop the csv, stuff it into a topic map by title -> tid.
    while ($csv_row = fgetcsv($csv_file, 0, ',', '"')) {
      // Flatten the CSV topic hierarchy.
      if (is_numeric($csv_row[2])) {
        // Index 0 is the parent level topic.
        if (!empty($csv_row[0])) {
          $topic_map[$csv_row[0]] = $csv_row[2];
        }
        // Index 1 is the child level topic.
        elseif (!empty($csv_row[1])) {
          $topic_map[$csv_row[1]] = $csv_row[2];
        }
      }
      else {
        continue;
      }
    }

    // Gather the type csv rows.
    $type_tids = [];
    $csv_file = fopen($this->configuration['file_stream']['type'], 'r');
    $type_map = [];
    // Loop the csv, stuff it into a topic map by title -> tid.
    while ($csv_row = fgetcsv($csv_file, 0, ',', '"')) {
      // Flatten the CSV topic hierarchy.
      if (is_numeric($csv_row[2])) {
        // Index 0 is the parent level topic.
        if (!empty($csv_row[0])) {
          $type_map[$csv_row[0]] = $csv_row[2];
        }
        // Index 1 is the child level topic.
        elseif (!empty($csv_row[1])) {
          $type_map[$csv_row[1]] = $csv_row[2];
        }
      }
      else {
        continue;
      }
    }

    // Loop taxonomies and map to topic tids.
    $source = $row->getSource();
    if (!empty($source['topics'])) {
      if (is_array($source['topics'])) {
        foreach ($source['topics'] as $topic) {
          $topic = trim($topic);
          if (!empty($topic_map[$topic])) {
            if (Term::load($topic_map[$topic])) {
              $topic_tids[] = ['target_id' => $topic_map[$topic]];
            }
          }
        }
      }
      elseif (is_string($source['topics'])) {
        $topic = trim($source['topics']);
        if (!empty($topic_map[$topic])) {
          if (Term::load($topic_map[$topic])) {
            $topic_tids[] = ['target_id' => $topic_map[$topic]];
          }
        }
      }
    }
    // Loop taxonomies and map to type tids.
    if (!empty($source['type'])) {
      if (is_array($source['type'])) {
        foreach ($source['type'] as $type) {
          $type = trim($type);
          if (!empty($type_map[$type])) {
            if (Term::load($type_map[$type])) {
              $type_tids[] = ['target_id' => $type_map[$type]];
            }
          }
        }
      }
      elseif (is_string($source['type'])) {
        $type = trim($source['type']);
        if (!empty($type_map[$type])) {
          if (Term::load($type_map[$type])) {
            $type_tids[] = ['target_id' => $type_map[$type]];
          }
        }
      }
    }

    // Trim Metadata Description.
    $meta_description = '';
    if (!empty($source['meta_description'])) {
      $meta_description = strlen($source['meta_description']) > 150
        ? substr($source['meta_description'], 0, 147) . "..."
        : $source['meta_description'];
    }

    // Prepare a shortened meta description.
    $row->setSourceProperty('prepare_meta_description', utf8_encode($meta_description));

    // We parse these later with sub_process on the topic reference field.
    $row->setSourceProperty('prepare_topic_tids', $topic_tids);

    // We parse these later with sub_process on the type reference field.
    $row->setSourceProperty('prepare_type_tids', $type_tids);

    return parent::prepareRow($row);
  }

}
