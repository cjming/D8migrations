<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Component\Utility\Html;

/**
 * Extract projects from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "projects"
 * )
 */
class Projects extends SqlBase {

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
      ->condition('n.type', 'project');
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
    if (empty($fid)) {
      // Set source for field title image.
      $rs_title_image = $this->getDatabase()->query('
      SELECT
        fld.field_title_image_fid
      FROM
        {field_data_field_title_image} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
      foreach ($rs_title_image as $record_image) {
        $fid = $record_image->field_title_image_fid;
      }
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

    // Set source for field project status.
    $rs_project_status = $this->getDatabase()->query('
      SELECT
        fld.field_activity_status_value
      FROM
        {field_data_field_activity_status} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_project_status as $record_status) {
      $row->setSourceProperty('field_activity_status', $record_status->field_activity_status_value);
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

    // Set source for related persons.
    $related_persons = [];
    $rs_related_persons = $this->getDatabase()->query('
      SELECT
        fld.field_members_target_id
      FROM
        {field_data_field_members} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_related_persons as $record_person) {
      $related_persons[] = [
        'nid' => $record_person->field_members_target_id,
      ];
    }
    if (!empty($related_persons)) {
      $row->setSourceProperty('field_members', $related_persons);
    }

    // Set source for related projects.
    $related_projects = [];
    // Get related projects from parent project field table.
    $rs_parent_projects = $this->getDatabase()->query('
      SELECT
        fld.field_parent_project_target_id
      FROM
        {field_data_field_parent_project} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_parent_projects as $record_project) {
      $related_projects[] = [
        'nid' => $record_project->field_parent_project_target_id,
      ];
    }
    // Get related projects from replaces field table.
    $rs_related_projects = $this->getDatabase()->query('
      SELECT
        fld.field_replaces_target_id
      FROM
        {field_data_field_replaces} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_related_projects as $record_replaced) {
      $related_projects[] = [
        'nid' => $record_replaced->field_replaces_target_id,
      ];
    }
    // Set source for related projects.
    if (!empty($related_projects)) {
      $row->setSourceProperty('field_project', $related_projects);
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
