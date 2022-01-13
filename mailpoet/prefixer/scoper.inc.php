<?php

use Isolated\Symfony\Component\Finder\Finder;

return [
  'prefix' => 'MailPoetVendor',
  'finders' => [
    Finder::create()
      ->files()
      ->ignoreVCS(true)
      ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
      ->exclude([
        'doc',
        'test',
        'test_old',
        'tests',
        'Tests',
        'vendor-bin',
        'composer',
      ])
      ->in('vendor'),
  ],

  // Whitelists a list of files. Unlike the other whitelist related features, this one is about completely leaving
  // a file untouched.
  // Paths are relative to the configuration file unless if they are already absolute
  'files-whitelist' => [],

  // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
  // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
  // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
  // heart contents.
  //
  // For more see: https://github.com/humbug/php-scoper#patchers
  'patchers' => [
    function (string $filePath, string $prefix, string $contents): string {
      // Change the contents here.
      if (preg_match('~vendor/symfony/polyfill-[^/]+/bootstrap\.php~', $filePath)) {
        return str_replace(
          'namespace MailPoetVendor;',
          '',
          $contents
        );
      }
      return $contents;
    },
  ],

  // PHP-Scoper's goal is to make sure that all code for a project lies in a distinct PHP namespace. However, you
  // may want to share a common API between the bundled code of your PHAR and the consumer code. For example if
  // you have a PHPUnit PHAR with isolated code, you still want the PHAR to be able to understand the
  // PHPUnit\Framework\TestCase class.
  //
  // A way to achieve this is by specifying a list of classes to not prefix with the following configuration key. Note
  // that this does not work with functions or constants neither with classes belonging to the global namespace.
  //
  // Fore more see https://github.com/humbug/php-scoper#whitelist
  'whitelist' => [],

  // If `true` then the user defined constants belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
  'whitelist-global-constants' => true,

  // If `true` then the user defined classes belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
  'whitelist-global-classes' => false,

  // If `true` then the user defined functions belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
  'whitelist-global-functions' => true,
];
