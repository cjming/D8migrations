<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the editor redirects.
 *
 * @MigrateSource(
 *   id = "editor_redirects"
 * )
 */
class EditorRedirects extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select editor created redirects.
    $query = $this->select('wp_redirection_items', 'wpri');
    $query->fields('wpri', array_keys($this->editorRedirectFields()));
    $query->condition('wpri.action_data', '/ask-us%', 'NOT LIKE');
    return $query;
  }

  /**
   * Returns the EditorRedirects fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function editorRedirectFields() {
    $fields = array(
      'id' => $this->t('Redirect ID'),
      'url' => $this->t('Url'),
      'action_data' => $this->t('Action Data'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->editorRedirectFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'id' => array(
        'type' => 'integer',
        'alias' => 'wpri',
      ),
    );
  }
}
