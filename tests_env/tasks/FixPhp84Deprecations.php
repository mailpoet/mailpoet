<?php declare(strict_types = 1);

namespace MailPoet\Tasks;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Fix PHP 8.4 deprecation: "Implicitly marking parameter $x as nullable is deprecated,
 * the explicit nullable type must be used instead"
 *
 * This script finds and fixes function/method parameters that have a default value of null
 * but are not explicitly marked as nullable with the ? prefix.
 */

class FixPhp84Deprecations {
  private array $vendorDirectories = [
    'vendor',
  ];

  public function run(): void {
    // Only run on PHP 8.4
    if (version_compare(PHP_VERSION, '8.4.0', '<')) {
      return;
    }

    foreach ($this->vendorDirectories as $vendorDir) {
      if (is_dir($vendorDir)) {
        $this->processDirectory($vendorDir);
      }
    }
  }

  private function processDirectory(string $directory): void {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->getExtension() === 'php') {
        $this->processFile($file->getPathname());
      }
    }
  }

  private function processFile(string $filePath): void {
    $content = file_get_contents($filePath);
    if ($content === false) {
      return;
    }

    $originalContent = $content;

    $content = $this->fixImplicitlyNullableParameters($content);

    // Only write if changes were made
    if ($content !== $originalContent) {
      file_put_contents($filePath, $content);
    }
  }

  private function fixImplicitlyNullableParameters(string $content): string {
    // Find potential matches
    $pattern = '/(\s|,|\()\s*([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)(\s+\$\w+\s*=\s*null\b)/';

    $content = preg_replace_callback($pattern, function($matches) {
      $fullMatch = $matches[0];
      $prefix = $matches[1];
      $type = $matches[2];
      $paramPart = $matches[3];

      // Additional safety checks
      if (strpos($fullMatch, '|') !== false) {
        return $fullMatch; // Don't modify union types
      }

      // Check if already nullable (look for ? before the type in the prefix)
      if (strpos($prefix, '?') !== false) {
        return $fullMatch; // Already nullable
      }

      // Don't modify visibility modifiers or storage class specifiers
      $excludedKeywords = ['static', 'private', 'protected', 'public', 'const', 'var'];
      if (in_array(trim($type), $excludedKeywords)) {
        return $fullMatch;
      }

      // Don't modify 'mixed' type since it already includes null
      if (trim($type) === 'mixed') {
        return $fullMatch;
      }

      return $prefix . '?' . $type . $paramPart;
    }, $content);

    return $content;
  }
}

$fixer = new FixPhp84Deprecations();
$fixer->run();
