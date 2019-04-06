# example_wordpressD8_migrate

## Migration Notes
### wp_redirect
The comments redirect migration requires a number of additional steps to be completed before the migration can be run.

1. Export comments data from WordPress DB to comments CSV.
1. Run migration using comments CSV as source.
1. Export redirects to CSV using script in migration code.
1. Import redirects into Dish D8 site via `wp_redirect` migration.

## Migration Steps
Migrations should be run in the following order.

```
drush mi wp_users
drush mi wp_terms
drush mi wp_files_user
drush mi wp_files
drush mi wp_media_user --force
drush mi wp_media --force
drush mi wp_posts --force
drush mi wp_redirect
```

Note on the use of `--force`:

> I believe the force is needed due to some items being intentionally skipped, which causes subsequent migrations to fail as they think previous ones did not finish.

### Drush commands to update migration based upon latest configuration.
drush config-import --partial --source=~/project/web/modules/custom/example_D7D8_migrate/config/install/ -y
drush config-import --partial --source=~/project/web/sites/www.wordpressD8.com/modules/example_wordpressD8_migrate/config/install/ -y
