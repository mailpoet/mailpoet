<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
  'exclude-files' => [],

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

  // If `true` then the user defined constants belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposing-constants
  'expose-global-constants' => true,

  // If `true` then the user defined classes belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposing-classes
  'expose-global-classes' => false,

  // If `true` then the user defined functions belonging to the global namespace will not be prefixed.
  //
  // For more see https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposing-functions
  'expose-global-functions' => true,
];
