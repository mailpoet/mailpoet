<?php

$replacements = [
  [
    'file' => '../vendor-prefixed/twig/twig/src/ExpressionParser.php',
    'find' => [
      '\'Twig\\\\Node\\\\Expression\\\\TestExpression\'',
      '\'Twig\\\\Node\\\\Expression\\\\FunctionExpression\'',
      '\'Twig\\\\Node\\\\Expression\\\\FilterExpression\'',
    ],
    'replace' => [
      '\'MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\TestExpression\'',
      '\'MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\FunctionExpression\'',
      '\'MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\FilterExpression\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/TwigFilter.php',
    'find' => [
      '\'\\\\Twig\\\\Node\\\\Expression\\\\FilterExpression\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\FilterExpression\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/TwigFunction.php',
    'find' => [
      '\'\\\\Twig\\\\Node\\\\Expression\\\\FunctionExpression\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\FunctionExpression\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/TwigTest.php',
    'find' => [
      '\'\\\\Twig\\\\Node\\\\Expression\\\\TestExpression\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Node\\\\Expression\\\\TestExpression\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/NotInBinary.php',
    'find' => [
      '\'!twig_in_filter(\'',
    ],
    'replace' => [
      '\'!\\\\MailPoetVendor\\\\twig_in_filter(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/InBinary.php',
    'find' => [
      '\'twig_in_filter(\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_in_filter(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Extension/CoreExtension.php',
    'find' => [
      '\'twig_date_format_filter\'',
      '\'twig_date_modify_filter\'',
      '\'twig_replace_filter\'',
      '\'twig_number_format_filter\'',
      '\'twig_round\'',
      '\'twig_urlencode_filter\'',
      '\'twig_jsonencode_filter\'',
      '\'twig_convert_encoding\'',
      '\'twig_title_string_filter\'',
      '\'twig_capitalize_string_filter\'',
      '\'twig_trim_filter\'',
      '\'twig_spaceless\'',
      '\'twig_join_filter\'',
      '\'twig_split_filter\'',
      '\'twig_sort_filter\'',
      '\'twig_array_merge\'',
      '\'twig_array_batch\'',
      '\'twig_array_filter\'',
      '\'twig_array_map\'',
      '\'twig_array_reduce\'',
      '\'twig_reverse_filter\'',
      '\'twig_length_filter\'',
      '\'twig_slice\'',
      '\'twig_first\'',
      '\'twig_last\'',
      '\'_twig_default_filter\'',
      '\'twig_get_array_keys_filter\'',
      '\'twig_escape_filter\'',
      '\'twig_upper_filter\'',
      '\'twig_lower_filter\'',
      '\'twig_escape_filter_is_safe\'',
      '\'_twig_escape_js_callback\'',
      '\'_twig_escape_css_callback\'',
      '\'_twig_escape_html_attr_callback\'',
      '\'twig_source\'',
      '\'twig_test_empty\'',
      '\'_twig_markup2string\'',
      '\'twig_test_iterable\'',
      '\'twig_random\'',
      '\'twig_date_converter\'',
      '\'twig_cycle\'',
      '\'twig_constant\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_date_format_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_date_modify_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_replace_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_number_format_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_round\'',
      '\'\\\\MailPoetVendor\\\\twig_urlencode_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_jsonencode_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_convert_encoding\'',
      '\'\\\\MailPoetVendor\\\\twig_title_string_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_capitalize_string_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_trim_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_spaceless\'',
      '\'\\\\MailPoetVendor\\\\twig_join_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_split_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_sort_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_array_merge\'',
      '\'\\\\MailPoetVendor\\\\twig_array_batch\'',
      '\'\\\\MailPoetVendor\\\\twig_array_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_array_map\'',
      '\'\\\\MailPoetVendor\\\\twig_array_reduce\'',
      '\'\\\\MailPoetVendor\\\\twig_reverse_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_length_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_slice\'',
      '\'\\\\MailPoetVendor\\\\twig_first\'',
      '\'\\\\MailPoetVendor\\\\twig_last\'',
      '\'\\\\MailPoetVendor\\\\_twig_default_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_get_array_keys_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_escape_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_upper_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_lower_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_escape_filter_is_safe\'',
      '\'\\\\MailPoetVendor\\\\_twig_escape_js_callback\'',
      '\'\\\\MailPoetVendor\\\\_twig_escape_css_callback\'',
      '\'\\\\MailPoetVendor\\\\_twig_escape_html_attr_callback\'',
      '\'\\\\MailPoetVendor\\\\twig_source\'',
      '\'\\\\MailPoetVendor\\\\twig_test_empty\'',
      '\'\\\\MailPoetVendor\\\\_twig_markup2string\'',
      '\'\\\\MailPoetVendor\\\\twig_test_iterable\'',
      '\'\\\\MailPoetVendor\\\\twig_random\'',
      '\'\\\\MailPoetVendor\\\\twig_date_converter\'',
      '\'\\\\MailPoetVendor\\\\twig_cycle\'',
      '\'\\\\MailPoetVendor\\\\twig_constant\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/ForNode.php',
    'find' => [
      '= twig_ensure_traversable("',
    ],
    'replace' => [
      '= \\\\MailPoetVendor\\\\twig_ensure_traversable("',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/CheckSecurityNode.php',
    'find' => [
      '\'\\\\Twig\\\\Extension\\\\SandboxExtension\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/SandboxedPrintNode.php',
    'find' => [
      '\'\\\\Twig\\\\Extension\\\\SandboxExtension',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Environment.php',
    'find' => [
      '\'\\\\Twig\\\\Template\'',
      '\'Twig_Extension\'',
      '\'Twig\\\\Extension\\\\AbstractExtension\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Template\'',
      '\'MailPoetVendor\\\\Twig_Extension\'',
      '\'MailPoetVendor\\\\Twig\\\\Extension\\\\AbstractExtension\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Token.php',
    'find' => [
      '\'Twig\\\\Token::\'',
    ],
    'replace' => [
      '\'MailPoetVendor\\\\Twig\\\\Token::\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Profiler/Node/EnterProfileNode.php',
    'find' => [
      '\\\\Twig\\\\Profiler\\\\Profile',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\Twig\\\\Profiler\\\\Profile',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/ModuleNode.php',
    'find' => [
      '"use Twig\\\\Environment;',
      '"use Twig\\\\Markup;',
      '"use Twig\\\\Source;',
      '"use Twig\\\\Template;',
      '"use Twig\\\\Error\\\\LoaderError;',
      '"use Twig\\\\Error\\\\RuntimeError;',
      '"use Twig\\\\Sandbox\\\\SecurityError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;',
    ],
    'replace' => [
      '"use MailPoetVendor\\\\Twig\\\\Environment;',
      '"use MailPoetVendor\\\\Twig\\\\Markup;',
      '"use MailPoetVendor\\\\Twig\\\\Source;',
      '"use MailPoetVendor\\\\Twig\\\\Template;',
      '"use MailPoetVendor\\\\Twig\\\\Error\\\\LoaderError;',
      '"use MailPoetVendor\\\\Twig\\\\Error\\\\RuntimeError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;',
    ],
  ],
];

foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}

// Remove unwanted class aliases in lib/Twig subdirectories
// We need to keep first level files in lib/Twig since most of them are still needed
exec("find ../vendor-prefixed/twig/twig/lib/Twig -maxdepth 1 -mindepth 1 -type d -exec rm -rf '{}' \;");
// Fix rest of the files in lib
// Files in twig/lib directory contain class aliases which makes namespaced classes global
// e.g. \class_alias('MailPoetVendor\\Twig_CompilerInterface', 'Twig_CompilerInterface', \false);
$iterator = new RecursiveDirectoryIterator("../vendor-prefixed/twig/twig/lib", RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) === 'php') {
    $data = file_get_contents($file);
    $data = preg_replace('/\\\\class.alias.*MailPoetVendor.*\);/', '', $data, -1, $count);
    file_put_contents($file, $data);
  }
}
