<?php
namespace MailPoet\Twig;

class i18n extends \Twig_Extension {

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
    $functions = array('localize', '__', '_n');

    foreach($functions as $function) {
      $twig_functions[] = new \Twig_SimpleFunction(
        $function,
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
    $output[] = ' var MailPoetI18n = MailPoetI18n || {}';
    foreach($translations as $key => $translation) {
      $output[] =
        'MailPoetI18n["'.$key.'"] = "'. str_replace('"', '\"', $translation) . '";';
    }
    $output[] = '</script>';
    return join("\n", $output);
  }

  function __() {
    $args = func_get_args();

    return call_user_func_array('__', $this->setTextDomain($args));
  }

  function _n() {
    $args = func_get_args();

    return call_user_func_array('_n', $this->setTextDomain($args));
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
