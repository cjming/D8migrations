# example_customD8_migrate_alpha

## Migration Steps

To see the current status of migrations:
```
drush ms
```
The Alpha tar file must be untarred before running migrations:
```
tar -xvzf ../web/modules/custom/example_customD8_migrate_alpha/data/xml/alpha_xml.tar.gz
```

The Alpha migrations involve ingesting protected files. The tar files for the protected Alpha content are too big to commit to the repo so they must be rsynced to test servers and prod, then untarred before running migrations.

The latest version of the Alpha protected files dumps are available at ________________

The tar files must be rsynced to a specific folder in the files system:
```
public://alpha/data/alpha-protected-xml-1.tar.gz
public://alpha/data/alpha-protected-xml-2.tar.gz
```
Untar the protected tar files:
```
tar -xvzf ../sites/default/files/alpha/data/alpha-protected-xml-1.tar.gz
tar -xvzf ../sites/default/files/alpha/data/alpha-protected-xml-2.tar.gz
```
After migrations are finished, everything inside `public://alpha/data/` should be deleted.

Due to dependencies, migrations must be run in the following order:

```
drush migrate-import alpha_files
drush migrate-import alpha_files_media
drush migrate-import alpha_files_pages
drush migrate-import alpha_pages
drush migrate-import alpha_redirects
```
## Migration Commands
To reset a migration to Idle after an error:
```
drush migrate-reset-status alpha_files
```

To rollback a migration:
```
drush migrate-rollback alpha_files
```

## Migration Flags
To see feedback in real time, add flags to migration commands.
https://drushcommands.com/drush-8x/migrate/migrate-import/

```
drush migrate-import alpha_files --feedback="500 items"
```

To limit the number of items to import, add the limit flag

```
drush migrate-import alpha_files --limit=10
```

To force update a migration (which should still happen in the proper sequence),
add the `--force` and `--update` flags:
```
drush migrate-import alpha_files --feedback="250 items" --force --update
```

### Drush command to update migration based upon latest configuration.
```
drush config-import --partial
--source=~/pathtoproject/web/modules/custom/example_customD8_migrate_alpha/config/install/ -y
```
