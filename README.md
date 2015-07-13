# namp2

Not another MP2 a.k.a MP3 done the right way.

# Requirements.

- PHP >=5.3.6 (production).
- PHP >=5.5 (dev).

# Install.

- Clone the repo in wp-content/plugins.
- Install PHP (OSX comes with it).
- Install composer.
```sh
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```
- Install composer.
```sh
$ php composer.phar install
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

- /test
Acceptance and spec tests.

- /mailpoet.php
Kickstart file.

# Rules.

- Two spaces indentation, Ruby style.
- CamelCase for classes.
- snake_case for methods.
- Max line length at 80 chars.
- ...
