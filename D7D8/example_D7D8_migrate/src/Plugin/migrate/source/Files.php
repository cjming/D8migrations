<?php

namespace Drupal\example_D7D8_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for Files (images, docs).
 *
 * @MigrateSource(
 *   id = "files"
 * )
 */
class Files extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $file_types = [
      'image/jpeg',
      'image/png',
      'image/gif',
      'text/plain',
      'application/pdf',
      'application/msword',
      'application/vnd.ms-excel',
      'application/vnd.ms-powerpoint',
      'application/vnd.download',
      'application/octet-stream',
    ];
    return $this->select('file_managed')
      ->fields('file_managed', array_keys($this->fields()))
      // Ignore unpublished files.
      ->condition('status', '1', '=')
      // Only interested in image files.
      ->condition('filemime', $file_types, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('User ID'),
      'filename' => $this->t('File name'),
      'uri' => $this->t('File URI'),
      'filemime' => $this->t('File MIME type'),
      'timestamp' => $this->t('File created date UNIX timestamp'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'fid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set the destination uri.
    $row->setSourceProperty('destination_uri', sprintf('public://%s', $row->getSourceProperty('filename')));

    // Update filepath to remove public:// directory portion.
    $original_path = $row->getSourceProperty('uri');
    $new_path = str_replace('public://', 'https://www.D7D8.edu/sites/www.D7D8.edu/files/', $original_path);
    $row->setSourceProperty('filepath', $new_path);
    return parent::prepareRow($row);
  }

}
