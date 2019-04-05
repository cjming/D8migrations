<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for attachments.
 *
 * @MigrateSource(
 *   id = "attachments"
 * )
 */
class Attachments extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select published posts.
    $query = $this->select('wp_posts', 'p');
    $query->fields('p', array_keys($this->attachmentFields()));
    $query->condition('p.post_status', 'inherit', '=');
    $query->condition('p.post_type', 'attachment', '=');
    $query->join('wp_posts', 'pp', 'p.post_parent = pp.id');
    $query->condition('pp.post_status', 'publish', '=');
    return $query;
  }

  /**
   * Returns the Posts fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function attachmentFields() {
    $fields = [
      'id' => $this->t('Attachment ID'),
      'post_excerpt' => $this->t('Image caption'),
      'post_content' => $this->t('Image description'),
      'post_author' => $this->t('Authored by (uid)'),
      'post_type' => $this->t('Post type'),
      'post_title' => $this->t('Image title'),
      'post_modified' => $this->t('Modified date'),
      'post_date' => $this->t('Created date'),
      'guid' => $this->t('Image URL'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->attachmentFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $file_name = \Drupal::service('file_system')->basename($row->getSourceProperty('guid'));
    $row->setSourceProperty('filename', $file_name);
    $row->setSourceProperty('destination_uri', sprintf('public://%s', $file_name));

    // Fix the URL.
    $guid = $row->getSourceProperty('guid');
    $guid = str_replace(
      'http://wordpress.beta.D8.com',
      'http://wordpress.D8.com',
      $guid
    );
    $guid = str_replace(
      'http://12.3.45.67',
      'http://wordpress.D8.com',
      $guid
    );
    $guid = str_replace(
      'http://12.3.45.678',
      'http://wordpress.D8.com',
      $guid
    );
    $row->setSourceProperty('guid', $guid);

    return parent::prepareRow($row);
  }

}
