<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Users.
 *
 * @MigrateSource(
 *   id = "users"
 * )
 */
class Users extends SqlBase {

  /**
   * The base URL to user profile images.
   *
   * @var string
   */
  const PHOTO_BASE_URL = 'http://wordpress.D8.com/wp-content/authors/';

  /**
   * The destination URI for the user profile image.
   *
   * @var string
   */
  const PHOTO_DESTINATION = 'public://profile/';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('wp_users', 'u');
    $query->fields('u', array_keys($this->userFields()));
    $query->condition('u.user_email', array_keys($this->userRoles()), 'IN');
    return $query;
  }

  /**
   * Returns the User fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function userFields() {
    $fields = array(
      'id' => $this->t('User ID'),
      'user_login' => $this->t('Username'),
      'user_pass' => $this->t('Password'),
      'user_email' => $this->t('Email address'),
      'user_registered' => $this->t('Created time'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->postFields();
    $fields['role'] = $this->t('Role');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Verify this is user we want to import and set their role if they were
    // found in the whitelisted users array.
    $role = $this->userRole($row->getSourceProperty('user_email'));
    if (empty($role)) {
      return FALSE;
    }
    $row->setSourceProperty('role', $role);

    // Collect additional information about the user.
    $fields = [
      'author_image',
      'first_name',
      'last_name',
      'description',
      '__last_active',
      'twitter',
      'facebook',
    ];
    // Get the user photo.
    $meta = $this->select('wp_usermeta', 'm')
      ->fields('m', ['meta_key', 'meta_value'])
      ->condition('meta_key', $fields, 'IN')
      ->condition('user_id', $row->getSourceProperty('id'), '=')
      ->execute()
      ->fetchAllKeyed();
    if (!empty($meta['author_image'])) {
      $row->setSourceProperty('user_photo_url',
        self::PHOTO_BASE_URL . $meta['author_image']
      );
    }
    $row->setSourceProperty('first_name', $meta['first_name']);
    $row->setSourceProperty('last_name', $meta['last_name']);
    $row->setSourceProperty('description', $meta['description']);
    $row->setSourceProperty('last_active', $meta['__last_active']);
    $row->setSourceProperty('facebook', $meta['facebook']);
    $row->setSourceProperty('twitter', $meta['twitter']);
    return parent::prepareRow($row);
  }

  /**
   * Finds the role associated with a user by email address.
   *
   * @param string $email
   *   A email address.
   *
   * @return string|NULL
   *   The role machine name the user should have.
   */
  function userRole($email) {
    $user_roles = $this->userRoles();
    if (array_key_exists($email, $user_roles)) {
      return strtolower($user_roles[$email]);
    }
  }

  /**
   * An array keyed by user email address with the user's roles.
   *
   * @return array
   *   An array of users to import.
   */
  function userRoles() {
    return [
      'hi@wordpressD8.com' => 'Administrator',
      'hello@wordpressD8.com' => 'Contributor',
      'editorial@wordpressD8.com' => 'Contributor',
      'info@wordpressD8.com' => 'Contributor',
      'contact@wordpressD8.com' => 'Contributor',
      'questions@wordpressD8.com' => 'Administrator',
      'feedback@wordpressD8.com' => 'Contributor',
    ];
  }
}
