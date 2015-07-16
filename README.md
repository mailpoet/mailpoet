# namp2

Not another MP2 a.k.a MP3 done the right way.

# Requirements.

- PHP >=5.3.6 (production).
- PHP >=5.5 (dev).

# Install.

- Clone the repo in wp-content/plugins.
- Install PHP (OSX comes with it).
- Install NodeJS.
- Install composer.
```sh
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```
- Install dependencies.
```sh
$ php composer.phar install
```
- Instal NPM
```sh
$ npm install npm -g
```
- Install Stylus
```sh
$ npm install stylus -g
```

# Structure.

- /assets
CSS and JS.

- /lang
Language files.

- /lib
MailPoet classes. All classes are autoloaded, under the MailPoet namespace.
```php
namespace \MailPoet\Models;
class Subscriber {}
```
```php
$subscriber = new \MailPoet\Models\Subscriber();
```

- /tests
Acceptance and spec tests.

- /mailpoet.php
Kickstart file.

# Unit testing.

Unit tests are in /tests/unit. You can just duplicate a Cest file and start testing. Methods available for testing come from Verify:
https://github.com/Codeception/Verify
```
$ vendor/bin/codecept run
```


# Stylus command
stylus -w assets/css/src/*.styl -o assets/css/
