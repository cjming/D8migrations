# example_customD8_migrate_beta

## Migration Steps

To see the current status of Beta migrations:
```
drush ms
```
The Beta tar file must be untarred before running migrations:
```
tar -xvzf ../web/modules/custom/example_customD8_migrate_beta/data/xml/example_customD8_beta_xml.tar.gz
```

Due to dependencies, Beta migrations must be run in the following order:

```
drush migrate-import beta_files
drush migrate-import beta_files_media
drush migrate-import beta_files_pages
drush migrate-import beta_pages
drush migrate-import beta_redirects
```
## Migration Commands
To reset a migration to Idle after an error:
```
drush migrate-reset-status beta_files
```

To rollback a migration:
```
drush migrate-rollback beta_files
```

## Migration Flags
To see feedback in real time, add flags to migration commands.
https://drushcommands.com/drush-8x/migrate/migrate-import/

```
drush migrate-import beta_files --feedback="500 items"
```

To limit the number of items to import, add the limit flag

```
drush migrate-import beta_files --limit=10
```

To force update a migration (which should still happen in the proper sequence),
add the `--force` and `--update` flags:
```
drush migrate-import beta_files --feedback="250 items" --force --update
```

### Drush command to update migration based upon latest configuration.
```
drush config-import --partial
--source=~/pathtoproject/web/modules/custom/example_customD8_migrate_beta/config/install/ -y
```
