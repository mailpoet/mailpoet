<?php

// inject the following code to all PHP files in given directory to prevent full path disclosure
// (directly used PHP file may output path-leaking errors, such as some used symbols are missing)
$code = "if (!defined('ABSPATH')) exit;";

// process command line arguments
if (count($argv) !== 2) {
  $cmd = basename(__FILE__);
  fwrite(STDERR, "This command injects full path disclosure prevention code to all '*.php' files in given <directory>.\n");
  fwrite(STDERR, "Usage:\n\n  $cmd <directory>\n");
  exit(1);
}

// absolutize path (if not absolute)
$directory = rtrim($argv[1]. '/');
if (mb_substr($directory, 0, 1) !== DIRECTORY_SEPARATOR) {
  $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
}

// iterate all files in given directory recursively
$iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) !== 'php') {
    continue;
  }

  // determine first line after which we can insert full path disclosure protection
  // (must be after open tag, strict_types declaration, and namespace declaration)
  $line = null;
  $data = file_get_contents($file);
  if ($data === false) {
    throw new Exception("Could not read file '$file'.");
  }
  $tokens = token_get_all($data);

  // find first PHP open tag
  for ($i = 0; $i < count($tokens); $i++) {
    $token = $tokens[$i];
    if (is_array($token) && $token[0] === T_OPEN_TAG) {
      $line = $token[2];
      break;
    }
  }

  if ($line === null) {
    continue;
  }

  // check for declare(strict_types=...)/namespace statements
  for (; $i < count($tokens); $i++) {
    $token = $tokens[$i];

    // try to find declare with 'strict_types'
    if (is_array($token) && $token[0] === T_DECLARE) {
      $found = false;
      $line_increment = 0;
      while ($tokens[++$i] !== ';') {
        $declare_token = $tokens[$i];
        if (is_array($declare_token) && $declare_token[0] === T_STRING && $declare_token[1] === 'strict_types') {
          $found = true;
        }
        $line_increment += substr_count(is_array($declare_token) ? $declare_token[1] : $declare_token, "\n");
      }

      if ($found) {
        $line = $token[2] + $line_increment;
      }
    }

    // try to find namespace declaration
    if (is_array($token) && $token[0] === T_NAMESPACE) {
      $line = $token[2];
      while ($tokens[++$i] !== ';') {
        $line += substr_count(is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i], "\n");
      }
      break; // when namespace declaration found we can end search
    }
  }

  // inject $code after line give by detected $line
  // NOTE: UTF-8 'u' modifier is not added on purpose since we only need to count '\n' occurrences
  //       and 'u' modifier breaks on some symbols (i.e. those used in Symfony's mb_string polyfill)
  $data = preg_replace(sprintf('/^(.*?\n){%u}/is', $line), "$0\n$code\n\n", $data);
  if ($data === null) {
    throw new Exception("Error when injecting code to file '$file'.");
  }

  $result = file_put_contents($file, $data);
  if ($result === false) {
    throw new Exception("Could not write file '$file'.");
  }
}
