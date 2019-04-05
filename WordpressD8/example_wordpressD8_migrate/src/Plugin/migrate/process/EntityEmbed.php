<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Drupal\media_entity\Entity\Media;

/**
 * Convert Wordpress captions and image to entity embed placeholders.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_embed"
 * )
 */
class EntityEmbed extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id, $plugin_definition,
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
    // First regex matches caption shortcodes with ids.
    $regex = '/(\[caption id="attachment_(?<id>\d+)" align="(?<alignment>\w+)".*?width="(?<width>\d+)"\].*\[\/caption\])/';
    $callback = [$this, 'convertImageMarkup'];
    $value = preg_replace_callback($regex, $callback, $value);

    // Next regex matches user photos.
    $regex = '/\[caption.*<img.*src=".*media-allrecipes\.com\/userphotos.*\/(?<filename>.*\..*?)".*\/>.*\[\/caption\]/';
    $callback = [$this, 'convertUserImageMarkup'];
    $value = preg_replace_callback($regex, $callback, $value);

    // Next regex matches caption shortcodes without ids and creates named
    // capture group for the source of the image tag.
    $regex = '/(\[caption id="(.*?)" align="(?<alignment>\w+)".*?width="(?<width>\d+)"\].*<img.*src="(?<source>.*?)".*\[\/caption\])/';
    $callback = [$this, 'convertImageMarkupNoID'];
    $value = preg_replace_callback($regex, $callback, $value);

    // Next regex matches img tags only and creates named
    // capture group for the source of the image tag.
    $regex = '/<img.*src="(?<source>.*?)".*\/>/';
    $callback = [$this, 'convertImageMarkupNoID'];
    $value = preg_replace_callback($regex, $callback, $value);

    // Next regex matches YouTube, Instagram, or Twitter anchor tags.
    $regex = '/<a\shref=\"http[s]?:\/\/(www\.)?(youtube|youtu\.be|instagram\.com\/p\/|twitter\.com\/.*?\/status).*?<\/a>/';
    $callback = [$this, 'convertMedia'];
    $value = preg_replace_callback($regex, $callback, $value);

    return $value;
  }

  /**
   * Converts attachment image tags by id into entity embeds.
   *
   * @param array $matches
   *   Array of matches from preg_replace_callback().
   *
   * @return string
   *   The image tag string to replace the skyword image tag with.
   */
  protected function convertImageMarkup(array $matches) {
    // Find the media entity associated with the embedded attachment.
    $migrations = $this->migrationPluginManager->createInstances(['wp_media']);
    $media_migration = current($migrations);
    $media_id = $media_migration->getIdMap()->lookupDestinationId([$matches['id']]);

    // If no media entity was found leave the existing markup in place.
    if (empty($media_id)) {
      return $matches[0];
    }

    // Load the media entity and build the image placeholder.
    if ($image = $this->entityManager->getStorage('media')->load(current($media_id))) {
      return $this->generateEmbedMarkup($image->uuid());
    }
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
  protected function convertImageMarkupNoID(array $matches) {
    // Extract the filename thru regex.
    $regex = '/<img.*src=".*\/(?<filename>.*?)".*\/>/';
    preg_match($regex, $matches[0], $filename);

    // Find the file entity id by file name.
    $file_id = \Drupal::entityQuery('file')
      ->condition('filename', current([$filename['filename']]), '=')
      ->execute();

    // If the file doesn't exist, try downloading from source.
    if (empty($file_id)) {
      // Verify that the image exists.
      try {
        $this->httpClient->get($matches['source'], $this->configuration['guzzle_options']);
      } // Guzzle throws an exception for anything but 200.
      catch (\Exception $e) {
        // Leave the existing markup in place if no file is found.
        return $matches[0];
      }
      // Save the file from source.
      $file = system_retrieve_file($matches['source'], 'public://', TRUE, FILE_EXISTS_REPLACE);
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
   * Converts user uploaded image tags into entity embeds.
   *
   * @param array $matches
   *   Array of matches from preg_replace_callback().
   *
   * @return string
   *   The image tag string to replace the skyword image tag with.
   */
  protected function convertUserImageMarkup(array $matches) {
    // Find the media entity associated with the embedded attachment.
    $migrations = $this->migrationPluginManager->createInstances(['wp_files_user']);
    $file_user_migration = current($migrations);
    $file_id = $file_user_migration->getIdMap()->lookupDestinationId([$matches['filename']]);

    // Load media entity by file name.
    $media_id = \Drupal::entityQuery('media')
      ->condition('field_media_image', current($file_id), '=')
      ->execute();

    // If no media entity was found leave the existing markup in place.
    if (empty($media_id)) {
      return $matches[0];
    }

    // Load the media entity and build the image placeholder.
    if ($image = $this->entityManager->getStorage('media')->load(end($media_id))) {
      return $this->generateEmbedMarkup($image->uuid());
    }
  }

  /**
   * Converts anchor tags with Social Media urls into entity embeds.
   *
   * @param array $matches
   *   Array of matches from preg_replace_callback().
   *
   * @return string
   *   The url embed code string to replace the anchor tag with.
   *
   * Supported formats: YouTube, Instagram, Twitter.
   */
  protected function convertMedia(array $matches) {
    // Extract the source url thru regex.
    $regex = '/<a\shref="(?<url>.*?)".*?>/';
    preg_match($regex, $matches[0], $media);
    $url = $media['url'];

    // Verify that the media exists.
    try {
      $this->httpClient->get($url, $this->configuration['guzzle_options']);
    } // Guzzle throws an exception for anything but 200.
    catch (\Exception $e) {
      // Leave the existing markup in place if url 404s.
      return $matches[0];
    }

    // Figure out which type of media this is.
    $type = '';
    if (strpos($url, 'youtu') !== FALSE) {
      $type = 'YouTube';
    }
    if (strpos($url, 'instagram') !== FALSE) {
      $type = 'Instagram';
    }
    if (strpos($url, 'twitter') !== FALSE) {
      $type = 'Twitter';
    }

    // Return the url embed code for the media url.
    return $this->generateMediaEmbedMarkup($url, $type);
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
      'uid' => '1',
      'status' => Media::PUBLISHED,
      'field_media_image' => [
        'target_id' => $fid,
        'alt' => t($filename),
        'title' => t($filename),
      ],
    ]);
    $image_media->save();
    return $image_media->id();
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
    return sprintf('<drupal-entity data-embed-button="image" data-entity-embed-display="view_mode:media.embed" data-entity-type="media" data-entity-uuid="%s"></drupal-entity>', $uuid);
  }

  /**
   * Builds the embed placeholder for Social Media embeds.
   *
   * @param string $url
   *   The url of the media asset.
   * @param string $type
   *   Media type - YouTube, Instagram, Twitter.
   *
   * @return string
   *   The embed placeholder.
   */
  protected function generateMediaEmbedMarkup($url, $type) {
    return sprintf('<drupal-url data-embed-button="url" data-embed-url="%s" data-entity-label="URL" data-url-provider="%s"></drupal-url>', $url, $type);
  }

}
