## Embedded WP
An module extension of [wp-browser](https://github.com/lucatume/wp-browser "lucatume/wp-browser · GitHub") to allow for server-less testing of WordPress plugins and themes.

### Installation
Require the package using [Composer](https://getcomposer.org/ "Composer") in the plugin or theme `composer.json` file

```json
{
    "require": {
        "lucatume/wp-embedded": "~1.0"
    }
}
```

and then use the [Codeception](http://codeception.com/ "Codeception - BDD-style PHP testing.") bootstrap command to scaffold tests and configuration

```bash
codeception bootstrap
```

Configure the `EmbeddedWp` module either in the the `codeception.yml` file or in the `functional.suite.yml` file

```yaml
modules:
    config:
        EmbeddedWp:
            dbName: false
            dbDir: false
            wpDebug: true
            tablePrefix: "wptests_"
            domain: "example.org"
            adminEmail: "admin@example.com"
            title: "Test Blog"
            phpBinary: "php"
            language: ""
            config_file: ""
            mainFile: my-plugin.php
            activatePlugins:
              - my-plugin.php
            booststrapActions: ''
```

Supposing the module will be used in the functional suite add `EmbeddedWP` to the used modules

```yaml
# Codeception Test Suite Configuration
#
# Suite for functional (integration) tests
# Emulate web requests and make application process them
# Include one of framework modules (Symfony2, Yii2, Laravel5) to use it

class_name: FunctionalTester
modules:
    enabled:
        - \Helper\Functional
        - EmbeddedWp
```

and run tests

```
codecept run
```

### How it works
The module wraps [WordPress automated testing suite](https://make.wordpress.org/core/handbook/testing/automated-testing/ "WordPress › Automated Testing « Make WordPress Core") and runs it using a SQLite server to remove the need for a MySQL server to be up, running and configured along with a WordPress installation while developing plugins or themes.  
The back-end integration needed to run WordPress using a SQLite database file is supplied by the [SQLite Integration](https://wordpress.org/plugins/sqlite-integration/ "WordPress › SQLite Integration « WordPress Plugins") plugin.  
The module packs a slightly modified version of it and a ready to use WordPress installation as well.  
That explains the hefty weight of the package.

### Purpose
The purpose of the module is to provide a quicker and lighter way to have a WordPress testing environment up and running to [TDD](https://en.wikipedia.org/wiki/Test-driven_development "Test-driven development - Wikipedia, the free encyclopedia") plugins and themes.  
For more integrated applications and testing refer to [wp-browser](https://github.com/lucatume/wp-browser "lucatume/wp-browser · GitHub") and this module is not meant to be a replacement of it.

### Configuration
Lacking a real database connection the configuration of the module lacks many of [wp-browser](https://github.com/lucatume/wp-browser "lucatume/wp-browser · GitHub") parameters and modifies some.  

* `mainFile` - string, required, def. `my-plugin.php`; the path, relative to the project root folder, to the main plugin or theme file
* `dbName` - string|bool, optional, def. `false`; by default the SQLite database file will be stored in the vendor folder under `lucatume/wp-embedded/src/embeddded-wordpres/database/` under the `wordpress` name: this setting allows specifying the name of the database file.
* `dbDir` - string|bool, optional, def. `false`; by default the SQLite database file will be stored in the vendor folder under `lucatume/wp-embedded/src/embeddded-wordpres/database/` : this setting allows specifying the path, relative to the the `lucatume/wp-embedded/src/embeddded-wordpres` path, of the database folder.
* `wpDebug` - bool, optional, def. `true`; the value of the `WP_DEBUG` constant
* `tablePrefix` - string, optional, def `wptests_`; the value of the `table_prefix` global
* `domain` - string, optional, def. `example.org`; the domain that will be used to install WordPress for the tests
* `adminEmail` -  string, optional, def. `admin@example.com`; the site administrator email used in the tests
* `title` - string, optional, def. `Test Blog`; the title that will be used for the site in the tests
* `phpBinary` - string, optional, def. `php`; the path or alias of the PHP system executable used in tests
* `language` - string, optional, def. ` `; the WordPress installation language
* `config_file` - string|array, optional, def. ` `; the name of extra configuration files the suite should load before the tests, the suite will look for those in the root of the project
* `activatePlugins` - string|array, optional, def. `my-plugin.php`; a list of plugin files that should be activated, using the `activate_{$plugin}` action, before the tests run
* `booststrapActions` - string|array, optional, def. ` `; a list of actions that will be called after the plugin files have been required and the activation hooks ran

A typical configuration to test a plugin with a main file called `my-plugin.php` would look like this:

```yaml
modules:
    config:
        EmbeddedWp:
            mainFile: my-plugin.php
            activatePlugins:
              - my-plugin.php
```
