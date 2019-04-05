<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extract user images from post body content.
 *
 * @MigrateSource(
 *   id = "user_images"
 * )
 */
class UserImages extends SourcePluginBase implements ContainerFactoryPluginInterface, RequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->state = $state;
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
      $container->get('state')
    );
  }

  /**
   * Gets the database connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      // See if the database info is in state - if not, fallback to
      // configuration.
      if (isset($this->configuration['database_state_key'])) {
        $this->database = $this->setUpDatabase($this->state->get($this->configuration['database_state_key']));
      }
      elseif (($fallback_state_key = $this->state->get('migrate.fallback_state_key'))) {
        $this->database = $this->setUpDatabase($this->state->get($fallback_state_key));
      }
      else {
        $this->database = $this->setUpDatabase($this->configuration);
      }
    }
    return $this->database;
  }

  /**
   * Gets a connection to the referenced database.
   *
   * This method will add the database connection if necessary.
   *
   * @param array $database_info
   *   Configuration for the source database connection. The keys are:
   *    'key' - The database connection key.
   *    'target' - The database connection target.
   *    'database' - Database configuration array as accepted by
   *      Database::addConnectionInfo.
   *
   * @return \Drupal\Core\Database\Connection
   *   The connection to use for this plugin's queries.
   *
   * @throws \Drupal\migrate\Exception\RequirementsException
   *   Thrown if no source database connection is configured.
   */
  protected function setUpDatabase(array $database_info) {
    if (isset($database_info['key'])) {
      $key = $database_info['key'];
    }
    else {
      // If there is no explicit database configuration at all, fall back to a
      // connection named 'migrate'.
      $key = 'migrate';
    }
    if (isset($database_info['target'])) {
      $target = $database_info['target'];
    }
    else {
      $target = 'default';
    }
    if (isset($database_info['database'])) {
      Database::addConnectionInfo($key, $target, $database_info['database']);
    }
    try {
      $connection = Database::getConnection($target, $key);
    }
    catch (ConnectionNotDefinedException $e) {
      // If we fell back to the magic 'migrate' connection and it doesn't exist,
      // treat the lack of the connection as a RequirementsException.
      if ($key == 'migrate') {
        throw new RequirementsException("No database connection configured for source plugin " . $this->pluginId, [], 0, $e);
      }
      else {
        throw $e;
      }
    }
    return $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if ($this->pluginDefinition['requirements_met'] === TRUE) {
      $this->getDatabase();
    }
  }

  /**
   * Wrapper for database select.
   */
  protected function select($table, $alias = NULL, array $options = []) {
    $options['fetch'] = \PDO::FETCH_ASSOC;
    return $this->getDatabase()->select($table, $alias, $options);
  }

  /**
   * Adds tags and metadata to the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query with additional tags and metadata.
   */
  protected function prepareQuery() {
    $this->query = clone $this->query();
    $this->query->addTag('migrate');
    $this->query->addTag('migrate_' . $this->migration->id());
    $this->query->addMetaData('migration', $this->migration);

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $this->prepareQuery();
    $results = $this->query->execute()->fetchCol();
    foreach ($results as $result) {
      $matches = [];
      preg_match_all(
        '/\[caption.*(?:align="(?<alignment>.*?)").*?\]<img\ (?=.*src="(?<src>.*?)")(?=.*width="(?<width>\d+)")(?=.*alt="(?<alt>.*?)")(?=.*id="__wp-temp-img-id").*\/>(?<caption>.*)\[\/caption\]/',
        $result,
        $matches,
        PREG_SET_ORDER
      );
      foreach ($matches as $match) {
        $file_name = \Drupal::service('file_system')->basename($match['src']);
        $this->dataRows[] = [
          'url' => $match['src'],
          // 'title' => $match['alt'],
          'alt' => $match['alt'],
          'caption' => $match['caption'],
          'filename' => $file_name,
          'destination_uri' => sprintf('public://%s', $file_name),
        ];
      }
    }
    return new \ArrayIterator($this->dataRows);
  }

  /**
   * Prints the query string when the object is used as a string.
   *
   * @return string
   *   The query string.
   */
  public function __toString() {
    return (string) $this->query();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'url' => $this->t('Image URL'),
      'alt' => $this->t('Image alt text'),
      'title' => $this->t('Image title'),
      'caption' => $this->t('Image caption'),
      'filename' => $this->t('File name'),
      'destination_uri' => $this->t('Destination URI'),
    ];
    return $fields;
  }

  /**
   * The query that finds the user uploaded images.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  protected function query() {
    // Select published posts.
    $query = $this->select('wp_posts', 'p')
      ->fields('p', ['post_content'])
      ->condition('post_content', '%__wp-temp-img-id%', 'LIKE')
      ->condition('post_status', 'publish', '=')
      ->condition('post_type', 'post', '=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $this->initializeIterator();
    return count($this->dataRows);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'filename' => [
        'type' => 'string',
      ],
    ];
  }

}
