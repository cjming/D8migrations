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

// Source db for D7D8 migrations.
$databases['example_D7']['default'] = array(
  'database' => 'D7',
  'username' => '***',
  'password' => '***',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
