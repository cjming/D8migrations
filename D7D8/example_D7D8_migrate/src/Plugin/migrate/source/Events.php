<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Unicode;

/**
 * Extract individual events from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "events"
 * )
 */
class Events extends SqlBase {

  /**
   * The base URL for videos.
   *
   * @var string
   */
  const VIDEO_BASE_URL = 'http://www.D7D8.edu/';

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
      ->condition('n.type', 'small_event');
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
      $subtitle = $record_subtitle->field_subtitle_value;
      if (Unicode::strlen($subtitle) > 255) {
        $subtitle = Unicode::truncate($subtitle, 255, TRUE, TRUE);
      }
      $row->setSourceProperty('field_subtitle', $subtitle);
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

    // Set source for field speaker.
    $speaker_nids = [];
    $rs_speaker = $this->getDatabase()->query('
      SELECT
        fld.field_speaker_target_id
      FROM
        {field_data_field_speaker} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_speaker as $record_speaker) {
      $speaker_nids[] = [
        'nid' => $record_speaker->field_speaker_target_id,
      ];
    }
    if (!empty($speaker_nids)) {
      $row->setSourceProperty('field_speaker', $speaker_nids);
    }

    // Set source for field event dates.
    $rs_event_dates = $this->getDatabase()->query('
      SELECT
        fld.field_event_date_value, fld.field_event_date_value2
      FROM
        {field_data_field_event_date} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_event_dates as $record_event_date) {
      $row->setSourceProperty('field_event_start', $record_event_date->field_event_date_value);
      $row->setSourceProperty('field_event_end', $record_event_date->field_event_date_value2);
    }

    // Set source for field courses.
    $course_nids = [];
    $rs_courses = $this->getDatabase()->query('
      SELECT
        fld.field_related_courses_target_id
      FROM
        {field_data_field_related_courses} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_courses as $record_course) {
      $course_nids[] = [
        'nid' => $record_course->field_related_courses_target_id,
      ];
    }
    if (!empty($course_nids)) {
      $row->setSourceProperty('field_courses', $course_nids);
    }

    // Set source for field related events.
    $events_nids = [];
    $rs_events = $this->getDatabase()->query('
      SELECT
        fld.field_related_events_target_id
      FROM
        {field_data_field_related_events} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_events as $record_event) {
      $events_nids[] = [
        'nid' => $record_event->field_related_events_target_id,
      ];
    }
    if (!empty($events_nids)) {
      $row->setSourceProperty('field_events', $events_nids);
    }

    // Set source for field related projects.
    $projects_nids = [];
    $rs_projects = $this->getDatabase()->query('
      SELECT
        fld.field_related_projects_target_id
      FROM
        {field_data_field_related_projects} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_projects as $record_project) {
      $projects_nids[] = $record_project->field_related_projects_target_id;
      $related_projects_nids[] = [
        'nid' => $record_project->field_related_projects_target_id,
      ];
    }
    if (!empty($projects_nids)) {
      $row->setSourceProperty('field_projects', $related_projects_nids);
    }
    // Check for related topics by project node.
    $project_nodes = Node::loadMultiple($projects_nids);
    // Get related topics through related projects.
    $event_tids = [];
    $topics_tids = [];
    foreach ($project_nodes as $project_node) {
      if ($this_tids = $this->getRelatedTopics($project_node)) {
        $event_tids = array_merge($event_tids, $this_tids);
      }
      // Dedupe topic tids.
      $topics_tids = array_map("unserialize", array_unique(array_map("serialize", $event_tids)));
    }
    // Set the source fields for topics.
    if (!empty($topics_tids)) {
      $row->setSourceProperty('field_topics', $topics_tids);
    }

    // Set source for field publications.
    $publication_nids = [];
    $rs_publication = $this->getDatabase()->query('
      SELECT
        fld.field_related_publications_target_id
      FROM
        {field_data_field_related_publications} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_publication as $record_publication) {
      $publication_nids[] = [
        'nid' => $record_publication->field_related_publications_target_id,
      ];
    }
    if (!empty($publication_nids)) {
      $row->setSourceProperty('field_publications', $publication_nids);
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

    // Set source for field related video.
    $videos = [];
    $video_nids = [];
    $rs_videos = $this->getDatabase()->query('
      SELECT
        fld.field_related_video_target_id
      FROM
        {field_data_field_related_video} fld
      WHERE
        fld.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($rs_videos as $record_video) {
      $video_nids[] = $record_video->field_related_video_target_id;
    }
    if (!empty($video_nids)) {
      $rs_video_titles = $this->getDatabase()->query('
        SELECT
          title, nid
        FROM
          {node}
        WHERE
          nid IN (:nids[])
      ', [':nids[]' => $video_nids]);
      foreach ($rs_video_titles as $record_video_title) {
        $videos[] = [
          'title' => $record_video_title->title,
          'uri' => self::VIDEO_BASE_URL . 'node/' . $record_video_title->nid,
        ];
      }
    }
    // Set source for field related video.
    if (!empty($videos)) {
      $row->setSourceProperty('field_related_video', $videos);
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
