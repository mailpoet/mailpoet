# MailPoet.

MailPoet done the right way.

# Install.

- Install system dependencies:
```
php
nodejs
wordpress
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

- Compile assets.
```sh
$ ./do compile:all
```

# Tests.

- Unit tests (using [verify](https://github.com/Codeception/Verify)):
```sh
$ ./do test:unit
```

- Debug tests:
```sh
$ ./do test:debug
```

# CSS
- [Stylus](https://learnboost.github.io/stylus/)
- [Nib extension](http://tj.github.io/nib/)

```sh
assets/css/src -> place your *.styl files here
```

### Watch for changes and recompile
```sh
$ ./do watch
```

## Module loading and organization

Our JS modules are stored in `assets/js/` folder. Modules should follow AMD module definition style:

```js
define('moduleName', ['dependency1', 'dependency2'], function(dependency1, dependency2){
  // Module code here

  return {
    // Module exports here
  };
})
```

Module loader will look for `dependency1` in `node_modules/` dependencies, as well as in `assets/js`. So you can use dependencies, defined in `package.json`, without the need of providing an absolute path to it.
Once found, dependencies will be injected into your module via function arguments.

When it comes to loading modules on a real page, WebPack uses "entry points" to create different bundles. In order for the module to be included in a specific bundle, it must be reachable from that bundle's entry point. [A good example on WebPack's website](http://webpack.github.io/docs/code-splitting.html#split-app-and-vendor-code).

Once javascript is compiled with `./do compile:javascript`, your module will be placed into a bundle. Including that bundle in a webpage will give provide you access to your module.

## Handlebars (`views/*.hbs`)

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

# i18n
- Use the regular WordPress functions in PHP and Twig:

```php
__()
_n()
```

```html
<p>
  <%= __('Click %shere%s!') | format('<a href="#">', '</a>') | raw %>
</p>
```

```html
<p>
  <%= _n('deleted %d message', 'deleted %d messages', count) | format(count) %>
  <!-- count === 1 -> "deleted 1 message" -->
  <!-- count > 1 -> "deleted $count messages" -->
</p>
```

- Handlebars.

You can use Twig i18n functions in Handlebars, just load your template from a Twig view.

# Publish

Before you run a publishing command, you need to:
1. Set up a local copy of MailPoet SVN repository in `.mp_svn` directory. Sample command: `svn co https://plugins.svn.wordpress.org/mailpoet/ .mp_svn`. The repo should be up-to-date.
2. Have all your features merged in Git `master`, your `mailpoet.php` and `readme.txt` tagged with a new version.
3. Run `./build.sh` to produce a `mailpoet.zip` distributable archive.

Everything's ready? Then run `./do publish`.
If the job goes fine, you'll get a message like this:
```
Go to '.mp_svn' and run 'svn ci -m "Release 3.0.0-beta.9"' to publish the
release
```
It's quite literal: you can review the changes to be pushed and if you're satisfied, run the suggested command to finish the release publishing process.
