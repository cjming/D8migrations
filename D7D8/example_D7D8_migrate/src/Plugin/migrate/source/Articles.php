<?php

namespace Drupal\example_D7D7_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Unicode;

/**
 * Extract articles from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "stories"
 * )
 */
class Articles extends SqlBase {

  /**
   * The base URL for audio and video.
   *
   * @var string
   */
  const MEDIA_BASE_URL = 'http://www.D7D8.edu/';

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
      ->condition('n.type', 'story');
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

    // Set source for related projects, topics, persons, courses, publications,
    // audio, and video.
    $related_content_nids = [];
    $topics_tids = [];
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
    // Get the related content nids and node objects.
    if (!empty($related_content_nids)) {
      // Init content nid arrays.
      $related_project_nids = [];
      $related_project_nodes = [];
      $related_person_nids = [];
      $related_publication_nids = [];
      $related_course_nids = [];
      $related_audio_nids = [];
      $related_video_nids = [];

      $content_nodes = Node::loadMultiple($related_content_nids);
      // Loop through content nodes to find different types.
      foreach ($content_nodes as $content_node) {
        // Build array of related project nids.
        if ($content_node->getType() == 'project') {
          $related_project_nids[] = [
            'nid' => $content_node->id(),
          ];
          // Build array of related project nodes to determine related topics.
          $related_project_nodes[] = $content_node;
        }
        // Build array of related person nids.
        if ($content_node->getType() == 'person') {
          $related_person_nids[] = [
            'nid' => $content_node->id(),
          ];
        }
        // Build array of related publication nids.
        if ($content_node->getType() == 'publication') {
          $related_publication_nids[] = [
            'nid' => $content_node->id(),
          ];
        }
        // Build array of related course nids.
        if ($content_node->getType() == 'course') {
          $related_course_nids[] = [
            'nid' => $content_node->id(),
          ];
        }
      }

      // Check for audio and video through node query because audio and video
      // nodes are not being migrated.
      $rs_content_titles = $this->getDatabase()->query('
        SELECT
          n.title, n.nid, n.type 
        FROM
          {node} n
        WHERE
          n.nid IN (:nids[])
      ', [':nids[]' => $related_content_nids]);
      foreach ($rs_content_titles as $record_content_title) {
        // Set source array for related audio.
        if ($record_content_title->type == 'audio') {
          $related_audio_nids[] = [
            'title' => $record_content_title->title,
            'uri' => self::MEDIA_BASE_URL . 'node/' . $record_content_title->nid,
          ];
        }
        // Set source array for related videos.
        if ($record_content_title->type == 'video') {
          $related_video_nids[] = [
            'title' => $record_content_title->title,
            'uri' => self::MEDIA_BASE_URL . 'node/' . $record_content_title->nid,
          ];
        }
      }

      // Set source fields for related projects and topics.
      if (!empty($related_project_nids)) {
        // Set the source fields for related projects.
        $row->setSourceProperty('field_projects', $related_project_nids);
        // Get related topics through related projects.
        $tids = [];
        foreach ($related_project_nodes as $related_project_node) {
          if ($this_tids = $this->getRelatedTopics($related_project_node)) {
            $tids = array_merge($tids, $this_tids);
          }
          // Dedupe topic tids.
          $topics_tids = array_map("unserialize", array_unique(array_map("serialize", $tids)));
        }
      }
      // Set source fields for related persons.
      if (!empty($related_person_nids)) {
        $row->setSourceProperty('field_persons', $related_person_nids);
      }
      // Set source fields for related publications.
      if (!empty($related_publication_nids)) {
        $row->setSourceProperty('field_publications', $related_publication_nids);
      }
      // Set source fields for related courses.
      if (!empty($related_course_nids)) {
        $row->setSourceProperty('field_courses', $related_course_nids);
      }
      // Set source fields for related audio.
      if (!empty($related_audio_nids)) {
        $row->setSourceProperty('field_related_audio', $related_audio_nids);
      }
      // Set source fields for related video.
      if (!empty($related_video_nids)) {
        $row->setSourceProperty('field_related_video', $related_video_nids);
      }
    }

    // Set related topics source field.
    // Check if the privacy topics term is already applied.
    $has_privacy_topic = FALSE;
    foreach ($topics_tids as $tid) {
      if ($tid['tid'] == '5') {
        $has_privacy_topic = TRUE;
        break;
      }
    }
    // Check if the privacy categories term is applied to append to topics tids.
    if (!$has_privacy_topic) {
      $rs_categories = $this->getDatabase()->query('
        SELECT
          fld.field_categories_tid
        FROM
          {field_data_field_categories} fld
        WHERE
          fld.entity_id = :nid
      ', [
        ':nid' => $nid,
      ]);
      foreach ($rs_categories as $record_category) {
        // If category term privacy is attached, apply the topic term privacy.
        if ($record_category->field_categories_tid == '2334') {
          $topics_tids[] = [
            'tid' => '5',
          ];
          break;
        }
      }
    }
    // Set the source field for related topics.
    if (!empty($topics_tids)) {
      $row->setSourceProperty('field_topics', $topics_tids);
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
