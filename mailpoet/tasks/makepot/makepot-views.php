<?php declare(strict_types = 1);

// Parse translation strings from HTML and HBS files in the "views" directory.
// The logic was extracted from an old custom makepot fork:
//   https://github.com/mailpoet/mailpoet/tree/0c9e445fea6ba9cfa8d5ff66e49565245e8ecf69/mailpoet/tasks/makepot

$basePathArg = $argv[1] ?? null;
if (!$basePathArg) {
  echo "No base path specified. Usage:\n\n    php tasks/makepot/makepot-views.php <base-path>\n";
  exit(1);
}

$basePath = realpath($basePathArg) . DIRECTORY_SEPARATOR;
$functionPatterns = [
  '/(__)\(\s*(([\'"]).+?\3)\s*\)/',
  '/(_x)\(\s*([\'"].+?[\'"],\s*[\'"].+?[\'"])\s*\)/',
  '/(_n)\(\s*([\'"].+?[\'"],\s*[\'"].+?[\'"],\s*.+?)\s*\)/',
];

function escape(string $string): string {
  return str_replace('"', '\\"', $string);
}

function getContext(string $function, array $args): ?string {
  return $function === '_x' ? ($args[1] ?? null) : null;
}

function getDomain(string $function, array $args): ?string {
  if ($function === '__') {
    return $args[1] ?? null;
  } elseif ($function === '_x') {
    return $args[2] ?? null;
  } elseif ($function === '_n') {
    return $args[3] ?? null;
  }
  return null;
}

function processFile(string $path): void {
  global $basePath;
  global $functionPatterns;

  $code = file_get_contents($path);
  $relPath = substr(realpath($path), strlen($basePath));

  $matches = [];
  foreach ($functionPatterns as $pattern) {
    preg_match_all($pattern, $code, $functionMatches, PREG_OFFSET_CAPTURE);
    for ($i = 0; $i < count($functionMatches[1]); $i += 1) {
      $matches[] = [
        'call' => $functionMatches[0][$i][0],
        'call_offset' => $functionMatches[0][$i][1],
        'name' => $functionMatches[1][$i][0],
        'arguments' => $functionMatches[2][$i][0],
      ];
    }
  }

  foreach ($matches as $match) {
    [$textBeforeMatch] = str_split($code, $match['call_offset']);
    $numberOfNewlines = strlen($textBeforeMatch) - strlen(str_replace("\n", "", $textBeforeMatch));
    $lineNumber = $numberOfNewlines + 1;

    $argumentsPattern = "/(?s)(?<!\\\\)(\"|')(?:[^\\\\]|\\\\.)*?\\1|[^,\\s]+/";
    preg_match_all($argumentsPattern, $match['arguments'], $argumentsMatches);

    $arguments = [];
    foreach ($argumentsMatches[0] as $argument) {
      // Remove surrounding quotes of the same type from argument strings
      $arguments[] = preg_replace("/^(('|\")+)(.*)\\1$/", "\\3", stripslashes($argument));
    }

    $function = $match['name'];
    $string = $arguments[0];
    $context = getContext($function, $arguments);
    $domain = getDomain($function, $arguments);
    $plural = $function === '_n' ? ($arguments[1] ?? null) : null;

    // The $domain was ignored in the old implementation - all domains are used.

    // print the .pot file data
    echo sprintf("#: %s:%d\n", $relPath, $lineNumber);
    if ($context) {
      echo sprintf("msgctxt \"%s\"\n", escape($context));
    }
    echo sprintf("msgid \"%s\"\n", escape($string));
    if ($plural) {
      echo sprintf("msgid_plural \"%s\"\n", escape($plural));
      echo "msgstr[0] \"\"\n";
      echo "msgstr[1] \"\"\n";
    } else {
      echo "msgstr \"\"\n";
    }
    echo "\n";
  }
}

// scan the "views" directory for HTML and HBS files
$dir = $basePath . DIRECTORY_SEPARATOR . 'views';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
  if (in_array(strtolower($file->getExtension()), ['html', 'hbs'], true)) {
    processFile($file->getRealPath());
  }
}
