<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Verify that a file exists and skips the row if it is not found.
 *
 * @MigrateProcessPlugin(
 *   id = "normalize_url"
 * )
 */
class NormalizeUrl extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a download process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    Client $http_client) {
    $configuration += [
      'guzzle_options' => [],
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Verify that the image exists.
    try {
      $this->httpClient->get($value, $this->configuration['guzzle_options']);
    }
    // Guzzle throws an exception for anything but 200.
    catch (\Exception $e) {
      throw new MigrateSkipRowException('Skipping attachment: "' . $value .'".');
    }

    return $value;
  }

}
