<?php

/**
 * Database settings.
 */
$databases['default']['default'] = array(
  'database' => 'D8',
  'username' => '***',
  'password' => '***',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

// Source db for Wordpress to D8 migrations.
$databases['wordpress']['default'] = array(
  'database' => 'wordpress_legacy',
  'username' => '***',
  'password' => '***',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
