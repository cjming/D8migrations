<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\example_wordpressD8_migrate\Plugin\migrate\process\TermMap;

/**
 * Extract terms from Wordpress database.
 *
 * @MigrateSource(
 *   id = "terms"
 * )
 */
class Term extends SqlBase {

  /**
   * The vocabularies to migrate.
   *
   * @var array
   */
  const VOCABULARIES = [
    'category',
    'post_tag',
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('wp_terms', 't');
    $query->fields('t', array_keys($this->termsFields()));
    $query->join('wp_term_taxonomy', 'tt', 'tt.term_id = t.term_id');
    $query->condition('tt.taxonomy', self::VOCABULARIES, 'IN');
    $query->isNotNull('t.name');

    return $query;
  }

  /**
   * Returns the User fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function termsFields() {
    $fields = array(
      'term_id' => $this->t('The term ID.'),
      'name' => $this->t('The name of the term.'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->termsFields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!TermMap::importTerm($row->getSourceProperty('term_id'))) {
      return FALSE;
    }
    // Load additional information for the term.
    $query = $this->select('wp_term_taxonomy', 'wptt')
      ->fields('wptt', ['parent', 'term_id', 'taxonomy', 'description'])
      ->condition('term_id', $row->getSourceProperty('term_id'))
      ->execute()
      ->fetchAssoc();

    if (!empty($query)) {
      $row->setSourceProperty('parent', $query['parent']);
      $row->setSourceProperty('description', $query['description']);
      $row->setSourceProperty('vid', $query['taxonomy']);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'term_id' => array(
        'type' => 'integer',
        'alias' => 't',
      ),
    );
  }

}
