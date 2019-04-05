<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;

/**
 * Extract publications from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "publications"
 * )
 */
class Publications extends SqlBase {

  /**
   * The base URL for images.
   *
   * @var string
   */
  const PHOTO_BASE_URL = 'http://www.D7D8.edu/sites/www.D7D8.edu/files/';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Query the built-in metadata.
    $query = $this->select('node', 'n')
      ->fields('n')
      ->condition('n.type', 'publication');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();
    $fields['body/value'] = $this->t('Full text of body');
    $fields['body/format'] = $this->t('Format of body');
    $fields['body/summary'] = $this->t('Summary of body');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');

    // Set source for field teaser.
    $rs_teaser = $this->getDatabase()->query('
      SELECT
        fld.field_teaser_value
      FROM
        {field_data_field_teaser} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_teaser as $record_teaser) {
      $teaser = Html::decodeEntities($record_teaser->field_teaser_value);
      $row->setSourceProperty('field_teaser', strip_tags($teaser));
    }

    // Set source for field body.
    $rs_body = $this->getDatabase()->query('
      SELECT
        fld.body_value
      FROM
        {field_data_body} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_body as $record_body) {
      $row->setSourceProperty('body_value', $record_body->body_value);
    }

    // Set source for field subtitle.
    $rs_subtitle = $this->getDatabase()->query('
      SELECT
        fld.field_subtitle_value
      FROM
        {field_data_field_subtitle} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_subtitle as $record_subtitle) {
      $row->setSourceProperty('field_subtitle', $record_subtitle->field_subtitle_value);
    }

    // Set source for field copyright holder.
    $rs_copyright_holder = $this->getDatabase()->query('
      SELECT
        fld.field_copyright_holder_value
      FROM
        {field_data_field_copyright_holder} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_copyright_holder as $record_copyright_holder) {
      $row->setSourceProperty('field_copyright_holder', $record_copyright_holder->field_copyright_holder_value);
    }

    // Set source for field license.
    $rs_license = $this->getDatabase()->query('
      SELECT
        fld.field_license_value
      FROM
        {field_data_field_license} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_license as $record_license) {
      $row->setSourceProperty('field_license', $record_license->field_license_value);
    }

    // Set source for field isbn.
    $rs_isbn = $this->getDatabase()->query('
      SELECT
        fld.field_isbn_value
      FROM
        {field_data_field_isbn} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_isbn as $record_isbn) {
      $row->setSourceProperty('field_isbn', $record_isbn->field_isbn_value);
    }

    // Set source for field publication date.
    $rs_publication_date = $this->getDatabase()->query('
      SELECT
        fld.field_publication_date_value
      FROM
        {field_data_field_publication_date} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_publication_date as $record_publication_date) {
      $row->setSourceProperty('field_publication_date', $record_publication_date->field_publication_date_value);
    }

    // Set source for field thumbnail.
    $rs_thumbnail = $this->getDatabase()->query('
      SELECT
        fld.field_thumbnail_fid
      FROM
        {field_data_field_thumbnail} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_thumbnail as $record_thumbnail) {
      $fid = $record_thumbnail->field_thumbnail_fid;
    }
    // Get the file id for the image.
    if (!empty($fid)) {
      $rs_file = $this->getDatabase()->query('
      SELECT
        fm.fid, fm.uri
      FROM
        {file_managed} fm
      WHERE
        fm.fid = :fid
    ', [':fid' => $fid]);
      foreach ($rs_file as $record_file) {
        $file_id = $record_file->fid;
        $uri = $record_file->uri;
      }
    }
    // Set field image file id.
    if (!empty($file_id)) {
      $row->setSourceProperty('field_image_fid', $file_id);
    }
    // Set uri for field image.
    if (!empty($uri)) {
      $uri = str_replace('public://', self::PHOTO_BASE_URL, $uri);
      $row->setSourceProperty('field_image', $uri);
    }

    // Set source for field files.
    $docs_fids = [];
    $rs_docs = $this->getDatabase()->query('
      SELECT
        fld.upload_fid
      FROM
        {field_data_upload} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_docs as $record_doc) {
      $docs_fids[] = [
        'fid' => $record_doc->upload_fid,
      ];
    }
    // Set field image file id.
    if (!empty($docs_fids)) {
      $row->setSourceProperty('field_files', $docs_fids);
    }

    // Set source array for field url.
    $urls = [];
    // Set source for field url.
    $rs_urls = $this->getDatabase()->query('
      SELECT
        fld.field_url_url, fld.field_url_title
      FROM
        {field_data_field_url} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_urls as $record_url) {
      $uri = strpos($record_url->field_url_url, 'http') !== FALSE ? $record_url->field_url_url : 'http://' . $record_url->field_url_url;
      $urls[] = [
        'title' => $record_url->field_url_title,
        'uri' => $uri,
      ];
    }
    if (!empty($urls)) {
      // Set source for field url - urls array.
      $row->setSourceProperty('field_url', $urls);
    }

    // Set source for field produced by.
    $producer_nids = [];
    $rs_produced = $this->getDatabase()->query('
      SELECT
        fld.field_produced_by_target_id
      FROM
        {field_data_field_produced_by} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_produced as $record_produced) {
      $producer_nids[] = [
        'nid' => $record_produced->field_produced_by_target_id,
      ];
    }
    if (!empty($producer_nids)) {
      $row->setSourceProperty('field_produced_by', $producer_nids);
    }

    // Set source for related projects and topics.
    $related_content_nids = [];
    // Get related content nodes from the field related content table.
    $rs_related_content = $this->getDatabase()->query('
      SELECT
        fld.field_related_content_target_id
      FROM
        {field_data_field_related_content} fld
      WHERE
        fld.entity_id = :nid
    ', [
      ':nid' => $nid,
    ]);
    foreach ($rs_related_content as $record_related_content) {
      $related_content_nids[] = $record_related_content->field_related_content_target_id;
    }
    // Get the related project nids and node objects.
    if (!empty($related_content_nids)) {
      $related_project_nids = [];
      $content_nodes = Node::loadMultiple($related_content_nids);
      // Loop through content nodes to find project types.
      foreach ($content_nodes as $content_node) {
        if ($content_node->getType() == 'project') {
          $related_project_nids[] = [
            'nid' => $content_node->id(),
          ];
          $related_project_nodes[] = $content_node;
        }
      }
      // Set source fields for projects and topics.
      if (!empty($related_project_nids)) {
        // Set the source fields for projects.
        $row->setSourceProperty('field_projects', $related_project_nids);
        $tids = [];
        foreach ($related_project_nodes as $related_project_node) {
          if ($this_tids = $this->getRelatedTopics($related_project_node)) {
            $tids = array_merge($tids, $this_tids);
          }
          // Dedupe topic tids.
          $topics_tids = array_map("unserialize", array_unique(array_map("serialize", $tids)));
        }
        // Set the source fields for topics.
        if (!empty($topics_tids)) {
          $row->setSourceProperty('field_topics', $topics_tids);
        }
      }
    }

    // Set source for path alias.
    $rs_alias = $this->getDatabase()->query('
      SELECT
        ua.alias
      FROM
        {url_alias} ua
      WHERE
        ua.source = :source
    ', [':source' => 'node/' . $nid]);
    foreach ($rs_alias as $record_alias) {
      $row->setSourceProperty('alias', '/' . $record_alias->alias);
    }

    return parent::prepareRow($row);
  }

  /**
   * Get the related topic term IDs by project nid.
   *
   * @param object $project_node
   *   A project node.
   *
   * @return array
   *   A array of topic term IDs.
   */
  protected function getRelatedTopics($project_node) {
    $topic_ids = [];
    foreach ($project_node->field_related_topics as $reference) {
      $topic_ids[] = [
        'tid' => $reference->target_id,
      ];
    }
    return $topic_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    return $ids;
  }

  /**
   * Returns the person base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Version ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'format' => $this->t('Format'),
      'teaser' => $this->t('Teaser'),
      'uid' => $this->t('Authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to front page'),
      'sticky' => $this->t('Sticky at top of lists'),
      'language' => $this->t('Language (fr, en, ...)'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'node';
  }

}
