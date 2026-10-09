<?php declare(strict_types = 1);

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Skip for production build
if (!file_exists(__DIR__ . '/../vendor/consolidation/robo/src/Common/CommandArguments.php')) {
  exit;
}

// Fixes for PHP8.2 Compatibility

// Development packages
$replacements = [
  // Robo patches can be removed after a new version with this fix is released:
  // https://github.com/consolidation/robo/issues/1135
  [
    'file' => __DIR__ . '/../vendor/consolidation/robo/src/Common/CommandArguments.php',
    'find' => [
      '$this->arguments .= \' \' . implode(\' \', array_map(\'static::escape\', $args));',
    ],
    'replace' => [
      '$this->arguments .= \' \' . implode(\' \', array_map([static::class, \'escape\'], $args));',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/consolidation/robo/src/Task/Base/Exec.php',
    'find' => [
      '$stopRunningJobs = Closure::fromCallable([\'self\', \'stopRunningJobs\']);',
    ],
    'replace' => [
      '$stopRunningJobs = Closure::fromCallable(self::class.\'::stopRunningJobs\');',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/consolidation/robo/src/Config/Config.php',
    'find' => [
      "const DECORATED = 'options.decorated';

    /**",
    ],
    'replace' => [
      "const DECORATED = 'options.decorated';
       private \$defaults = [];
    /**",
    ],
  ],
  // PHP 8.5: "Using null as an array offset". Fixed upstream in php-cli-tools 0.12.6:
  // https://github.com/wp-cli/php-cli-tools/pull/188
  [
    'file' => __DIR__ . '/../vendor/wp-cli/php-cli-tools/lib/cli/Colors.php',
    'find' => [
      'if (isset(self::$_colors[$type][$code])) {',
    ],
    'replace' => [
      'if (isset($code) && isset(self::$_colors[$type][$code])) {',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
