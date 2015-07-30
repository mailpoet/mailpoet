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

# JS Dependencies.
In order to use a JS library (let's take Handlebars as an example), you need to follow these steps:

* add "handlebars" as a dependency in the `package.json` file
```json
{
  "private": true,
  "dependencies": {
    "handlebars": "3.0.3",
  },
```
* run `./do install` (the handlebars module will be added into the node_modules folder)
* create a symlink to the file you want to use by running this command
```sh
# from the root of the project
$ cd assets/js/lib/
# /!\ use relative path to the node_modules folder
$ ln -nsf ../../../node_modules/handlebars/dist/handlebars.min.js handlebars.min.js
```
* make sure to push the symlink onto the repository

# Translations.
When editing a Twig template (`views/*.html`), you have to the following WordPress functions:

* `__`: returns a string
* `_n`: returns a pluralized string

```html
<p>{{ __('Click %shere%s!') | format('<a href="#">', '</a>') | raw }}</p>
```
This will print: "Click [here](#)"

```html
<p>{{ _n('deleted one message', 'deleted %d messages', count, 'wysija-newsletters') | format(count) }}</p>
```
This will print "deleted one message" (if count === 1)
This will print "deleted X message" (if count !== 1)