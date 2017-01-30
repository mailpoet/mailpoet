<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class I18n extends \Twig_Extension {

  private $_text_domain;

  function __construct($text_domain) {
    // set text domain
    $this->_text_domain = $text_domain;
  }

  function getName() {
    return 'i18n';
  }

  function getFunctions() {
    // twig custom functions
    $twig_functions = array();
    // list of WP functions to map
    $functions = array(
      'localize' => 'localize',
      '__' => 'translate',
      '_n' => 'pluralize',
      '_x' => 'translateWithContext',
      'date' => 'date'
    );

    foreach($functions as $twig_function => $function) {
      $twig_functions[] = new \Twig_SimpleFunction(
        $twig_function,
        array($this, $function),
        array('is_safe' => array('all'))
      );
    }
    return $twig_functions;
  }

  function localize() {
    $args = func_get_args();
    $translations = array_shift($args);
    $output = array();

    $output[] = '<script type="text/javascript">';
    foreach($translations as $key => $translation) {
      $output[] =
        'MailPoet.I18n.add("'.$key.'", "'. str_replace('"', '\"', $translation) . '");';
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

  function date() {
    $args = func_get_args();
    $date = (isset($args[0])) ? $args[0] : null;
    $date_format = (isset($args[1])) ? $args[1] : get_option('date_format');

    if(empty($date)) return;

    // check if it's an int passed as a string
    if((string)(int)$date === $date) {
      $date = (int)$date;
    } else if(!is_int($date)) {
      $date = strtotime($date);
    }

    return get_date_from_gmt(date('Y-m-d H:i:s', $date), $date_format);
  }

  private function setTextDomain($args = array()) {
    // make sure that the last argument is our text domain
    if($args[count($args) - 1] !== $this->_text_domain) {
      // otherwise add it to the list of arguments
      $args[] = $this->_text_domain;
    }
    return $args;
  }
}
