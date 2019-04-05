<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Component\Utility\Unicode;

/**
 * Extract persons from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "persons"
 * )
 */
class Persons extends SqlBase {

  /**
   * The base URL to user profile images.
   *
   * @var string
   */
  const PHOTO_BASE_URL = 'http://www.D7D8.edu/sites/www.D7D8.edu/files/';

  /**
   * The destination URI for the user profile image.
   *
   * @var string
   */
  const PHOTO_DESTINATION = 'public://';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Query the built-in metadata.
    $query = $this->select('node', 'n')
      ->fields('n')
      ->condition('n.type', 'person');
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

    // Set source for field short bio.
    $rs_teaser = $this->getDatabase()->query('
      SELECT
        fld.field_teaser_value
      FROM
        {field_data_field_teaser} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_teaser as $record_teaser) {
      $row->setSourceProperty('field_short_bio', $record_teaser->field_teaser_value);
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
    // If body value is empty, set the teaser for the body value.
    if (empty($record_body) && !empty($record_teaser)) {
      $row->setSourceProperty('body_value', $row->getSourceProperty('field_short_bio'));
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
    foreach ($rs_thumbnail as $record) {
      $fid = $record->field_thumbnail_fid;
    }
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
    // Set field thumbnail file id.
    if (!empty($file_id)) {
      $row->setSourceProperty('field_thumbnail_fid', $file_id);
    }
    // Set uri for field thumbnail.
    if (!empty($uri)) {
      $uri = str_replace('public://', self::PHOTO_BASE_URL, $uri);
      $row->setSourceProperty('field_thumbnail', $uri);
    }

    // Set source for field last name.
    $rs_last_name = $this->getDatabase()->query('
      SELECT
        fld.field_last_name_value
      FROM
        {field_data_field_last_name} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_last_name as $record) {
      $row->setSourceProperty('field_last_name', $record->field_last_name_value);
    }
    // Set source for field job title.
    $rs_job_title = $this->getDatabase()->query('
      SELECT
        fld.field_job_title_value
      FROM
        {field_data_field_job_title} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_job_title as $record) {
      $job_title = $record->field_job_title_value;
      if (Unicode::strlen($job_title) > 255) {
        $job_title = Unicode::truncate($job_title, 255, TRUE, TRUE);
      }
      $row->setSourceProperty('field_job_title', strip_tags($job_title));
    }

    // Set source for field email.
    $rs_email = $this->getDatabase()->query('
      SELECT
        fld.field_email_email
      FROM
        {field_data_field_email} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_email as $record) {
      $row->setSourceProperty('field_email', $record->field_email_email);
    }

    // Set source for field Berkman member.
    $rs_berkman_member = $this->getDatabase()->query('
      SELECT
        fld.field_berkman_member_value
      FROM
        {field_data_field_berkman_member} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_berkman_member as $record) {
      $berkman_member = $record->field_berkman_member_value == 'yes' ? 1 : 0;
      $row->setSourceProperty('field_berkman_member', $berkman_member);
    }

    // Set source for field role.
    $rs_berkman_role = $this->getDatabase()->query('
      SELECT
        fld.field_role_value
      FROM
        {field_data_field_role} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_berkman_role as $record) {
      switch ($record->field_role_value) {
        case 'Fellowship Advisory Board':
          $role = 'Fellows Advisory Board';
          break;

        case 'Student':
        case 'Research Assistant':
        case 'CRCS':
          $role = 'None';
          break;

        case 'Emeritus':
          $role = 'Alumni';
          break;

        default:
          $role = $record->field_role_value;
      }
      $row->setSourceProperty('field_role', $role);
    }

    // Set source array for field links.
    $links = [];
    // Set source for field links - source: external url.
    $rs_homepage = $this->getDatabase()->query('
      SELECT
        fld.field_homepage_url, fld.field_homepage_title
      FROM
        {field_data_field_homepage} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_homepage as $record_homepage) {
      $uri = strpos($record_homepage->field_homepage_url, 'http') !== FALSE ? $record_homepage->field_homepage_url : 'http://' . $record_homepage->field_homepage_url;
      $links[] = [
        'title' => $record_homepage->field_homepage_title,
        'uri' => $uri,
      ];
    }

    // Set source for field links - source: external blog.
    $rs_blog = $this->getDatabase()->query('
      SELECT
        fld.field_blog_url, fld.field_blog_title
      FROM
        {field_data_field_blog} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_blog as $record_blog) {
      $uri = strpos($record_blog->field_blog_url, 'http') !== FALSE ? $record_blog->field_blog_url : 'http://' . $record_blog->field_blog_url;
      $links[] = [
        'title' => $record_blog->field_blog_title,
        'uri' => $uri,
      ];
    }
    // Set source for field links - links array.
    $row->setSourceProperty('field_links', $links);

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
