<?php

namespace Drupal\example_customD8_migrate_alpha\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Import a file as a side-effect of a migration.
 *
 * @MigrateProcessPlugin(
 *   id = "alpha_file_import"
 * )
 */
class AlphaFileImport extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there is no URL.
      throw new MigrateSkipRowException();
    }
    // If filepath contains member or mem, it is protected content.
    $contains_mem = strpos($value, '/mem/');
    $contains_member = strpos($value, '/member/');

    // Check if file is public or private based on string in file path.
    $streamwrapper = ($contains_mem === FALSE && $contains_member === FALSE) ? 'public://alpha/' : 'private://alpha/';

    // Set the member folder.
    $member_folder = $contains_mem !== FALSE ? 'mem' : '';
    $member_folder = $contains_member !== FALSE ? 'member' : $member_folder;

    // Extract the file name.
    $file_parts = pathinfo($value);
    if (!empty($member_folder)) {
      $filepath = str_replace("http://www.alpha.com/$member_folder/", '', $file_parts['dirname'] . '/');
    }
    else {
      $filepath = str_replace("http://www.alpha.com/", '', $file_parts['dirname'] . '/');
    }
    $filename = $file_parts['basename'];

    // If the destination folder can't be created, throw error.
    $create_dir = file_prepare_directory($streamwrapper, FILE_CREATE_DIRECTORY);
    if (!$create_dir) {
      throw new RequirementsException('Destination folder could not be created.');
    }

    // Download file from remote server and save to public.
    if ($streamwrapper == 'public://alpha/') {
      // Save the file, return its ID.
      $file = system_retrieve_file($value, $streamwrapper, TRUE, FILE_EXISTS_REPLACE);
    }
    // Grab the file from a local directory and save to private.
    else {
      // Set the relative path to the protected DCR/XML files based on url.
      $private_file = 'public://alpha/data/' . $member_folder . '/' . $filepath . $filename;

      // Check for and load the xml file.
      if (file_exists($private_file)) {
        $file = file_save_data(file_get_contents($private_file), 'private://alpha/' . $filename, FILE_EXISTS_REPLACE);
      }
    }
    if (!$file) {
      // Skip this item if saving the file fails.
      throw new MigrateSkipProcessException();
    }
    return $file->id();
  }

}
