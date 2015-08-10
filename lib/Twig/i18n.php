<?php
namespace MailPoet\Twig;

class i18n extends \Twig_Extension {

  private $_text_domain;

  public function __construct($text_domain) {
    // set text domain
    $this->_text_domain = $text_domain;
  }

  public function getName() {
    return 'i18n';
  }

  public function getFunctions() {
    // twig custom functions
    $twig_functions = array();
    // list of WP functions to map
    $functions = array('__', '_n');

    foreach($functions as $function) {
      $twig_functions[] = new \Twig_SimpleFunction(
        $function,
        array($this, $function),
        array('is_safe' => array('all'))
      );
    }
    return $twig_functions;
  }

  public function __() {
    $args = func_get_args();

    return call_user_func_array('__', $this->setTextDomain($args));
  }

  public function _n() {
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
