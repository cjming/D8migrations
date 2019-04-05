<?php

namespace Drupal\example_customD8_migrate_gamma\Plugin\migrate\process;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Drupal\media\Entity\Media;

/**
 * Convert images into entity embed placeholders.
 *
 * @MigrateProcessPlugin(
 *   id = "gamma_entity_embed"
 * )
 */
class GammaEntityEmbed extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The file stream wrapper to use i.e public://.
   *
   * @var string
   */
  protected $streamWrapper;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Remote domain.
   *
   * @var string
   */
  protected $remoteDomain;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
  $plugin_definition,
    MigrationInterface $migration,
    MigrationPluginManagerInterface $migration_plugin_manager,
    EntityTypeManagerInterface $entity_manager,
    Client $http_client) {
    $configuration += [
      'guzzle_options' => [],
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->entityManager = $entity_manager;
    $this->httpClient = $http_client;
    $this->streamWrapper = !empty($this->local_stream) ? $this->local_stream : 'public://';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('entity_type.manager'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // If the remote domain isn't available, throw error.
    if (empty($this->remoteDomain = $this->configuration['remote_domain'])) {
      throw new RequirementsException('No remote domain supplied.');
    }

    // Regex matches img tags only and creates named capture group for the
    // source of the image tag.
    $regex = '/<img.*src="(?<source>.*?)".*\/>/';
    $callback = [$this, 'convertImageMarkup'];
    $value = preg_replace_callback($regex, $callback, $value);

    // Grab the allowed file types for file_public.field_media_file.
    $allowed_types = explode(' ', \Drupal::config('field.field.media.file.field_media_file')->get('settings.file_extensions'));
    // Loop through file types, calling regex replace callback for each one.
    foreach ($allowed_types as $type) {
      // Regex matches links to files of allowed file types.
      // It also matches the href, filetype and title for later use.
      $regex = '/<a[^>]*href=[\'\"]((?<href>[^>]*?)\.(?<filetype>' . $type . '?))[^>]*[\"\'][^>]*>(?<title>[^>]*?)<\/a>/';
      $callback = [$this, 'convertFileMarkup'];
      $value = preg_replace_callback($regex, $callback, $value);
    }

    return $value;
  }

  /**
   * Converts image tags by source or filename into entity embeds.
   *
   * @param array $matches
   *   Array of matches from preg_replace_callback().
   *
   * @return string
   *   The image tag string to replace the image tag with.
   */
  protected function convertImageMarkup(array $matches) {
    // Extract the filename thru regex.
    $regex = '/<img.*src="(?<filename>.*?)".*\/>/';
    preg_match($regex, $matches[0], $filename);

    // If the URL is an absolute and to another domain, ignore it.
    if (filter_var($matches['source'], FILTER_VALIDATE_URL)) {
      if (strpos($matches['source'], $this->remoteDomain) === FALSE) {
        return $matches[0];
      }
    }

    // Find the file entity id by file name.
    $file_id = \Drupal::entityQuery('file')
      ->condition('filename', current([$filename['filename']]), '=')
      ->execute();

    // If the file doesn't exist, try downloading from source.
    if (empty($file_id)) {
      // Verify that the image exists.
      $source = $this->remoteDomain . $matches['source'];
      try {
        $this->httpClient->get($source, $this->configuration['guzzle_options']);
      }
      // Guzzle throws an exception for anything but 200.
      catch (\Exception $e) {
        // Leave the existing markup in place if no file is found.
        return $matches[0];
      }
      // Save the file from source.
      $file = system_retrieve_file($source, $this->streamWrapper, TRUE, FILE_EXISTS_REPLACE);
      if (!$file) {
        // Leave the existing markup in place if file couldn't be saved.
        return $matches[0];
      }
      $file_id = $file->id();
    }

    // Make sure file id is string.
    $file_id = is_array($file_id) ? key($file_id) : $file_id;

    // Load media entity id by file id.
    $media_id = \Drupal::entityQuery('media')
      ->condition('field_media_image', $file_id, '=')
      ->execute();

    // If there's a file id but no attached media entity,
    // create a media entity from the file.
    if (empty($media_id)) {
      $media_id = $this->createMediaEntity($file_id, $filename['filename']);
    }

    // If a media entity wasn't created, leave the existing markup in place.
    if (empty($media_id)) {
      return $matches[0];
    }

    // Make sure media id is string.
    $media_id = is_array($media_id) ? key($media_id) : $media_id;

    // Load the media entity and build the image placeholder.
    if ($image = $this->entityManager->getStorage('media')->load($media_id)) {
      return $this->generateEmbedMarkup($image->uuid());
    }
  }

  /**
   * Grab PDF files and save them.
   *
   * @param array $matches
   *   Array of matches from preg_replace_callback().
   *
   * @return string
   *   The drupal entity code which replaces anchor tag.
   */
  protected function convertFileMarkup(array $matches) {
    $regex = '/\/.*\/(?<fileandtype>.*\.' . $matches['filetype'] . '?)$/';
    preg_match($regex, $matches[1], $filename);

    // If the URL is an absolute and to another domain, ignore it.
    if (filter_var($matches[1], FILTER_VALIDATE_URL)) {
      if (strpos($matches[1], $this->remoteDomain) === FALSE) {
        return $matches[0];
      }
    }

    // Find the file entity id by file name.
    $file_id = \Drupal::entityQuery('file')
      ->condition('filename', [trim($filename['fileandtype'])], '=')
      ->execute();

    // If the file doesn't exist, try downloading from source.
    if (empty($file_id)) {
      // Verify that the file exists.
      $source = (strpos($matches[1], $this->remoteDomain) === FALSE)
        ? $this->remoteDomain . $matches[1]
        : $matches[1];

      try {
        $this->httpClient->get($source, $this->configuration['guzzle_options']);
      }
      // Guzzle throws an exception for anything but 200.
      catch (\Exception $e) {
        // Leave the existing markup in place if no file is found.
        return $matches[0];
      }
      // Save the file from source.
      $file = system_retrieve_file($source, $this->streamWrapper, TRUE, FILE_EXISTS_REPLACE);
      if (!$file) {
        // Leave the existing markup in place if file couldn't be saved.
        return $matches[0];
      }
      $file_id = $file->id();
    }

    // Make sure file id is string.
    $file_id = is_array($file_id) ? key($file_id) : $file_id;
    // Load media entity id by file id.
    $media_id = \Drupal::entityQuery('media')
      ->condition('field_media_file', $file_id, '=')
      ->execute();

    // If there's a file id but no attached media entity,
    // create a media entity from the file.
    if (empty($media_id)) {
      $media_id = $this->createFilePublicMediaEntity($file_id);
    }

    // If a media entity wasn't created, leave the existing markup in place.
    if (empty($media_id)) {
      return $matches[0];
    }

    // For file we are building file embed, not media embed!
    if ($file = $this->entityManager->getStorage('file')->load($file_id)) {
      return $this->generateFilePublicEmbedMarkup($file->getFileUri(), $file->uuid(), $matches['title'], str_replace('/', '-', $file->getMimeType()));
    }
  }

  /**
   * Creates a media entity.
   *
   * @param string $fid
   *   A file id.
   * @param string $filename
   *   A filename.
   *
   * @return string
   *   The media id of the newly created media entity.
   */
  protected function createMediaEntity($fid, $filename) {
    $image_media = Media::create([
      'bundle' => 'image',
      'uid' => 1,
      'status' => 1,
      'field_media_image' => [
        'target_id' => $fid,
        'alt' => t('@filename', ['@filename' => $filename]),
        'title' => t('@filename', ['@filename' => $filename]),
      ],
    ]);
    $image_media->save();
    return $image_media->id();
  }

  /**
   * Creates a non-image media entity.
   *
   * @param string $fid
   *   A file id.
   *
   * @return string
   *   The media id of the newly created media entity.
   */
  protected function createFilePublicMediaEntity($fid) {
    // Load the file so we know what media object is saving.
    $file = $this->entityManager->getStorage('file')->load($fid);
    // Create the media file.
    $file_media = Media::create([
      'bundle' => 'file',
      'uid' => 1,
      'status' => 1,
      'name' => t('@filename', ['@filename' => $file->getFilename()]),
      'field_media_file' => [
        'target_id' => $fid,
        'display' => 'full',
      ],
    ]);
    $file_media->save();
    return $file_media->id();
  }

  /**
   * Builds the embed placeholder.
   *
   * @param string $uuid
   *   The uuid for the image entity.
   *
   * @return string
   *   The embed placeholder.
   */
  protected function generateEmbedMarkup($uuid) {
    return sprintf('<drupal-entity data-embed-button="media" data-entity-embed-display="entity_reference:media_thumbnail" data-entity-embed-display-settings="{&quot;image_style&quot;:&quot;thumbnail&quot;,&quot;image_link&quot;:&quot;&quot;}" data-entity-type="media" data-entity-uuid="%s"></drupal-entity>', $uuid);
  }

  /**
   * Builds the embed placeholder.
   *
   * @param string $uri
   *   The uri of the file to which we are linking.
   * @param string $uuid
   *   The uuid for the public file entity.
   * @param string $link_text
   *   The link text.
   * @param string $mime_type
   *   The file mime type.
   *
   * @return string
   *   The custom html output for links to files.
   */
  protected function generateFilePublicEmbedMarkup($uri, $uuid, $link_text, $mime_type) {
    // @todo. Dependency injection for these services.
    $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
    $file_embed = [
      '#theme' => 'gamma_dynamic_file_embed',
      '#attributes' => [
        'data-entity-type' => 'file',
        'data-entity-uuid' => $uuid,
        'data-caption' => t(':link_text', [':link_text' => $link_text]),
        'data-embed-button' => 'file_embed',
        'data-entity-embed-display' => 'file:file_default',
        'data-entity-embed-display-settings' => '{"use_description_as_link_text":1, "description": "' . $link_text . '"}',
      ],
      '#href' => file_url_transform_relative($wrapper->getExternalUrl()),
      '#link_text' => t(':link_text', [':link_text' => $link_text]),
      '#uuid' => $uuid,
      '#mime_type' => $mime_type,
    ];
    return \Drupal::service('renderer')->renderRoot($file_embed);
  }

}
