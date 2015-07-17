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
- Install composer.
```sh
$ php composer.phar install
```
- Instal NPM
```sh
$ npm install npm -g
```
- Install Bower
```
$ npm install bower -g
```
- Install Stylus
```sh
$ npm install stylus -g
```
- Install Nib (Stylus extension)
```sh
$ npm install nib -g
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

# Stylus command
stylus -u nib -w assets/css/src/admin.styl -o assets/css/