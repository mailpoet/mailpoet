# namp2

Not another MP2 a.k.a MP3 done the right way.

# Install.

- Install system dependencies:
```
php
nodejs
phantomjs
```

- Clone the repo in wp-content/plugins.

- Install composer.
```sh
$ curl -sS https://getcomposer.org/installer | php
$ ./composer.phar install
```

- Install dependencies.
```sh
$ ./do install
```

- Update dependencies when needed.
```sh
$ ./do update
```

# Structure.

- Dependencies.
```
# PHP dependencies.
composer.json
# JS dependencies.
package.json
```

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

- Run tests:
```
$ ./do test:unit
```

# Acceptance testing.

- Run a WordPress install at:
```
127.0.0.1:8888
```

- Run tests:
```
$ ./do tes:acceptance
```

# Watch assets.
```
$ ./do watch
```
