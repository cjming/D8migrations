<?php

namespace Drupal\example_D7D8migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determine file type.
 *
 * @MigrateProcessPlugin(
 *   id = "file_type"
 * )
 */
class FileType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $file_parts = pathinfo($value);
    $extension = strtolower($file_parts['extension']);
    switch ($extension) {
      case "jpg":
      case "jpeg":
      case "gif":
      case "png":
        $type = 'image';
        break;

      case "doc":
      case "pdf":
      case "ppt":
      case "xls":
      case "txt":
        $type = 'file';
        break;

      default:
        $type = 'file';
    }
    return $type;
  }

}
