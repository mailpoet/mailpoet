<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Twig;

use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\Extension\CoreExtension;
use MailPoetVendor\Twig\TwigFunction;

class Handlebars extends AbstractExtension {
  public function getFunctions() {
    return [
      new TwigFunction(
        'partial',
        [
          $this,
          'generatePartial',
        ],
        [
          'needs_environment' => true,
          'needs_context' => true,
          'is_safe' => ['all'],
        ]
      ),
    ];
  }

  public function generatePartial($env, $context) {
    // get arguments (minus env & $context)
    /** @var array{0:string, 1:array|string, 2:string} $args */
    $args = array_slice(func_get_args(), 2);
    $argsCount = count($args);

    // default values
    $alias = null;

    switch ($argsCount) {
      case 2:
        list($id, $file) = $args;
        break;
      case 3:
        list($id, $file, $alias) = $args;
        break;
      default:
        return;
    }

    $renderedTemplate = CoreExtension::include($env, $context, $file);

    $output = '<script id="' . $id . '" type="text/x-handlebars-template">' . "\n"
      . '  ' . $renderedTemplate . "\n"
      . '</script>';

    if ($alias !== null) {
      $output .= '<script type="text/javascript">' . "\n"
        . 'jQuery(function($) {' . "\n"
        . '  $(function() {' . "\n"
        . '    Handlebars.registerPartial(' . "\n"
        . "      '" . $alias . "',\n"
        . "      jQuery('#" . $id . "').html()\n"
        . '    );' . "\n"
        . '  });' . "\n"
        . '});' . "\n"
        . '</script>';
    }
    return $output;
  }
}
