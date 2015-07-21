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

- Copy .env.sample to .env.
```sh
$ cp .env.sample .env
```

# Structure.

- Dependencies.
```sh
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
// file: ./lib/models/subscriber.php
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

# Tests.

- Unit tests (using [verify](https://github.com/Codeception/Verify)):
```sh
$ ./do test:unit
```

- Acceptance tests:
```sh
# Setup .env
$ ./do test:acceptance
```

- Run all tests:
```sh
$ ./do test:all
```

# Watch assets.
```sh
$ ./do watch
```
