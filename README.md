# namp2

Not another MP2 a.k.a MP3 done the right way.

# Install.

- Install system dependencies:
```
php
nodejs
phantomjs
```

- Clone the repo in `wp-content/plugins`.

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

- Before running tests, make sure you specify the following values in the .env file:
```sh
WP_TEST_URL="http://wordpress.dev"
WP_TEST_USER="admin"
WP_TEST_PASSWORD="password"
WP_TEST_PATH="/absolute/path/to/wordpress"
```

- Unit tests (using [verify](https://github.com/Codeception/Verify)):
```sh
$ ./do test:unit
```

- Acceptance tests:
```sh
$ ./do test:acceptance
```

- Run all tests:
```sh
$ ./do test:all
```

# Assets.
## CSS
We are using [Stylus](https://learnboost.github.io/stylus/) (with the [Nib extension](http://tj.github.io/nib/)) as our CSS preprocessor.
### Structure
```sh
assets/css/lib -> link your dependencies here
assets/css/src -> place your *.styl files here
```

### Watch for changes and recompile
The following command will compile all specified *.styl files (in `Robofile.php`->`watch()`) into `assets/css`
```sh
$ ./do watch
```

### Add files to the watch command
```php
# Robofile.php
<?php
  function watch() {
    $files = array(
      # global admin styles
      'assets/css/src/admin.styl',
      # rtl specific styles
      'assets/css/src/rtl.styl',
      # <-- add custom file (*.styl)
    );
    ...
?>
```
## JS
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

# Views
## Twig (`views/*.html`)
### Layout
```html
<!-- system notices -->
<!-- main container -->
<div class="wrap">
  <!-- notices -->
  <!-- title block -->
  <% block title %><% endblock %>
  <!-- content block -->
  <% block content %><% endblock %>
</div>

<!-- stylesheets -->
<%= stylesheet(
  'admin.css'
)%>

<!-- rtl specific stylesheet -->
<% if is_rtl %>
  <%= stylesheet('rtl.css') %>
<% endif %>

<!-- javascripts -->
<%= javascript(
  'ajax.js',
  'notice.js',
  'modal.js',
  'lib/handlebars.min.js',
  'handlebars_helpers.js'
)%>

<!-- handlebars templates -->
<% block templates %><% endblock %>
```
### Page
```html
<% extends 'layout.html' %>

<% block title %>
  <h2 class="title"><%= form.form_name %></h2>
<% endblock %>

<% block content %>
  <p><%= __('Hello World!') %></p>
<% endblock %>
```

## Handlebars (`views/*.hbs`)

In order to include Handlebars templates (`views/*.hbs`) in your view (`views/*.html`).

You can either use the `partial(id, file, alias = null)` function or create your own custom template.

Templates included using `partial(id,...)` can be accessed via `jQuery('#'+id).html()`.

If you specify an `alias`, you will be able to reference it using `{{> alias }}` in any Handlebars template.

```html
<!-- use the `templates` block -->
<% block templates %>
  <!-- include a .hbs template -->
  <%= partial('my_template_1', 'form/templates/toolbar/fields.hbs') %>
  
  <!-- include a .hbs template and register it as a partial -->
  <%= partial('my_template_2', 'form/templates/blocks.hbs', '_my_partial') %>
  
  <!-- custom partial using partial defined above -->
  <script id="my_template_3" type="text/x-handlebars-template">
    {{> _my_partial }}
  </script>
<% endblock %>
```

# Internationalization (i18n)
## i18n in PHP
* use the regular WordPress functions (`__()`, `_e()`, `_n()`,...).
```php
<?php
  echo __('my translatable string');
  // or
  _e('my translatable string');
  
  // pluralize
  printf(
    _n('We deleted %d spam message.',
       'We deleted %d spam messages.',
       $count,
       'my-text-domain'
    ),
    $count
  );
?>
```
Reference: [i18n for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers)

## i18n in Twig
* `__(string)`: returns a string

```html
<p>
  <%= __('Click %shere%s!') | format('<a href="#">', '</a>') | raw %>
</p>
```
**/!\\** Notice that we use the `raw` filter so that the HTML remains unfiltered.

Here's the output:
```html
<p>
  Click <a href="#">here</a>
</p>
```

* `_n('singular', 'plural', value)`: returns a pluralized string
```html
<p>
  <%= _n('deleted %d message', 'deleted %d messages', count) | format(count) %>
  <!-- count === 1 -> "deleted 1 message" -->
  <!-- count > 1 -> "deleted $count messages" -->
</p>
```

## i18n in Handlebars
In order to use i18n functions, your Handlebars template needs to be loaded from Twig (`views/*.html`).

Then you can use the Twig functions in your template.