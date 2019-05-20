<?php

namespace MailPoet\Twig;

use MailPoet\Config\Localizer;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

if (!defined('ABSPATH')) exit;

class I18n extends AbstractExtension {

  private $_text_domain;

  function __construct($text_domain) {
    // set text domain
    $this->_text_domain = $text_domain;
  }

  function getFunctions() {
    // twig custom functions
    $twig_functions = [];
    // list of WP functions to map
    $functions = [
      'localize' => 'localize',
      '__' => 'translate',
      '_n' => 'pluralize',
      '_x' => 'translateWithContext',
      'get_locale' => 'getLocale',
      'date' => 'date',
    ];

    foreach ($functions as $twig_function => $function) {
      $twig_functions[] = new TwigFunction(
        $twig_function,
        [$this, $function],
        ['is_safe' => ['all']]
      );
    }
    return $twig_functions;
  }

  function localize() {
    $args = func_get_args();
    $translations = array_shift($args);
    $output = [];

    $output[] = '<script type="text/javascript">';
    foreach ($translations as $key => $translation) {
      $output[] =
        'MailPoet.I18n.add("' . $key . '", "' . str_replace('"', '\"', $translation) . '");';
    }
    $output[] = '</script>';
    return join("\n", $output);
  }

  function translate() {
    $args = func_get_args();

    return call_user_func_array('__', $this->setTextDomain($args));
  }

  function pluralize() {
    $args = func_get_args();

    return call_user_func_array('_n', $this->setTextDomain($args));
  }

  function translateWithContext() {
    $args = func_get_args();

    return call_user_func_array('_x', $this->setTextDomain($args));
  }

  function getLocale() {
    $localizer = new Localizer;
    return $localizer->locale();
  }

  function date() {
    $args = func_get_args();
    $date = (isset($args[0])) ? $args[0] : null;
    $date_format = (isset($args[1])) ? $args[1] : WPFunctions::get()->getOption('date_format');

    if (empty($date)) return;

    // check if it's an int passed as a string
    if ((string)(int)$date === $date) {
      $date = (int)$date;
    } else if (!is_int($date)) {
      $date = strtotime($date);
    }

    return WPFunctions::get()->getDateFromGmt(date('Y-m-d H:i:s', (int)$date), $date_format);
  }

  private function setTextDomain($args = []) {
    // make sure that the last argument is our text domain
    if ($args[count($args) - 1] !== $this->_text_domain) {
      // otherwise add it to the list of arguments
      $args[] = $this->_text_domain;
    }
    return $args;
  }
}
