# example_D7D8_migrate

## Migration Steps

To see the current status of migrations:
```
drush ms
```

Due to dependencies, D7D8 migrations must be run in the following order:

```
drush migrate-import users
drush migrate-import files
drush migrate-import media
drush migrate-import persons
drush migrate-import projects
drush migrate-import publications
drush migrate-import courses
drush migrate-import articles
drush migrate-import events
drush migrate-import pages
drush migrate-import redirects
```

## Migration Flags
To see feedback in real time, add flags to migration commands.
https://drushcommands.com/drush-8x/migrate/migrate-import/

```
drush migrate-import files --feedback="500 items"
```

To force update a migration (which should still happen in the proper sequence),
add the `--force` and `--update` flags:
```
drush migrate-import persons --feedback="250 items" --force --update
```

### Drush command to update migration based upon latest configuration.
```
drush config-import --partial
--source=~/project/web/modules/custom/example_D7D8_migrate/config/install/ -y
```
