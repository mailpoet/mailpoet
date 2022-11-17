<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/GetAttrExpression.php',
    'find' => [
      '\'twig_get_attribute(',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_get_attribute(',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/EqualBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/GreaterBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/GreaterEqualBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/LessBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/LessEqualBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/Binary/NotEqualBinary.php',
    'find' => [
      'twig_compare(\'',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_compare(\'',
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
      '\'twig_convert_encoding\'',
      '\'twig_title_string_filter\'',
      '\'twig_capitalize_string_filter\'',
      '\'twig_upper_filter\'',
      '\'twig_lower_filter\'',
      '\'twig_trim_filter\'',
      '\'twig_spaceless\'',
      '\'twig_join_filter\'',
      '\'twig_split_filter\'',
      '\'twig_sort_filter\'',
      '\'twig_array_merge\'',
      '\'twig_array_batch\'',
      '\'twig_array_column\'',
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
      '\'twig_constant\'',
      '\'twig_cycle\'',
      '\'twig_random\'',
      '\'twig_date_converter\'',
      '\'twig_include\'',
      '\'twig_source\'',
      '\'twig_test_empty\'',
      '\'twig_test_iterable\'',
      '\'twig_sprintf\'',
      '\'twig_striptags\'',
      '\'twig_nl2br\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_date_format_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_date_modify_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_replace_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_number_format_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_round\'',
      '\'\\\\MailPoetVendor\\\\twig_urlencode_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_convert_encoding\'',
      '\'\\\\MailPoetVendor\\\\twig_title_string_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_capitalize_string_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_upper_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_lower_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_trim_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_spaceless\'',
      '\'\\\\MailPoetVendor\\\\twig_join_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_split_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_sort_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_array_merge\'',
      '\'\\\\MailPoetVendor\\\\twig_array_batch\'',
      '\'\\\\MailPoetVendor\\\\twig_array_column\'',
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
      '\'\\\\MailPoetVendor\\\\twig_constant\'',
      '\'\\\\MailPoetVendor\\\\twig_cycle\'',
      '\'\\\\MailPoetVendor\\\\twig_random\'',
      '\'\\\\MailPoetVendor\\\\twig_date_converter\'',
      '\'\\\\MailPoetVendor\\\\twig_include\'',
      '\'\\\\MailPoetVendor\\\\twig_source\'',
      '\'\\\\MailPoetVendor\\\\twig_test_empty\'',
      '\'\\\\MailPoetVendor\\\\twig_test_iterable\'',
      '\'\\\\MailPoetVendor\\\\twig_sprintf\'',
      '\'\\\\MailPoetVendor\\\\twig_striptags\'',
      '\'\\\\MailPoetVendor\\\\twig_nl2br\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Extension/DebugExtension.php',
    'find' => [
      '\'twig_var_dump\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_var_dump\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Extension/EscaperExtension.php',
    'find' => [
      '\'twig_escape_filter\'',
      '\'twig_escape_filter_is_safe\'',
      '\'twig_raw_filter\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_escape_filter\'',
      '\'\\\\MailPoetVendor\\\\twig_escape_filter_is_safe\'',
      '\'\\\\MailPoetVendor\\\\twig_raw_filter\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Extension/StringLoaderExtension.php',
    'find' => [
      '\'twig_template_from_string\'',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_template_from_string\'',
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
      '"use Twig\\\\Extension\\\\SandboxExtension;',
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
      '"use MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension;',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/FunctionExpression.php',
    'find' => [
      'twig_constant_is_defined',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\twig_constant_is_defined',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/IncludeNode.php',
    'find' => [
      '\'twig_array_merge(',
      '\'twig_to_array(',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_array_merge(',
      '\'\\\\MailPoetVendor\\\\twig_to_array(',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/WithNode.php',
    'find' => [
      '(!twig_test_iterable(',
      '= twig_to_array(',
    ],
    'replace' => [
      '(!\\\\MailPoetVendor\\\\twig_test_iterable(',
      '= \\\\MailPoetVendor\\\\twig_to_array(',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/Expression/MethodCallExpression.php',
    'find' => [
      '\'twig_call_macro(',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\twig_call_macro(',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/CheckSecurityCallNode.php',
    'find' => [
      '\'\\\\Twig\\\\Extension\\\\SandboxExtension',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension',
    ],
  ],
];

foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}

// Remove unwanted class aliases in lib/Twig
exec("rm -rf ../vendor-prefixed/twig/twig/lib/Twig");
exec("rm ../vendor-prefixed/twig/twig/README.rst");
exec("rm -rf ../vendor-prefixed/twig/twig/src/Test");
