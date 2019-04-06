# example_customD8_migrate_gamma

## Setting up files for migrations

The tar file of the source XML is available here: ________________

The tar file of source XML must be rsynced to a specific folder in the files system:
```
public://gamma/data/gamma.tar.gz
```
Untar the source XML tar file:
```
tar -xvzf ../sites/default/files/gamma/data/gamma.tar.gz
```

The CSV mapping for taxonomy topic and type terms should be placed in the gamma data directory in the file system:

Download the following mappings:

Topics - https://google.com
Types - https://google.com

Then place the files here:
```
public://gamma/data/gamma-Taxonomy-TopicMapping.csv
public://gamma/data/gamma-Taxonomy-TypeMapping.csv
```

This file also needs to be updated in the migrate_plus.migration.gamma_articles.yml file.  
Locate the variable file_stream in the file source and update it there.

```
# The location of the csv taxonomy maps; the migration will fail if these are missing.
file_stream: 
  type: public://gamma/data/gamma-Taxonomy-TypeMapping.csv
  topic: public://gamma/data/gamma-Taxonomy-TopicMapping.csv
```

## Migration Steps

To see the current status of Gamma migrations:
```
drush ms
```

Due to dependencies, Gamma migrations must be run in the following order:

```
drush migrate-import gamma_authors
drush migrate-import gamma_articles
```
## Migration Commands
To reset a migration to Idle after an error:
```
drush migrate-reset-status gamma_articles
```

To rollback a migration:
```
drush migrate-rollback gamma_articles
```

## Migration Flags
To see feedback in real time, add flags to migration commands.
https://drushcommands.com/drush-8x/migrate/migrate-import/

```
drush migrate-import gamma_articles --feedback="300 items"
```

To limit the number of items to import, add the limit flag

```
drush migrate-import gamma_articles --limit=10
```

To force update a migration (which should still happen in the proper sequence),
add the `--force` and `--update` flags:
```
drush migrate-import gamma_articles --feedback="250 items" --force --update
```

### Drush command to update migration based upon latest configuration.
```
drush config-import --partial
--source=~/pathtoproject/web/modules/custom/example_customD8_migrate_gamma/config/install/ -y
```
