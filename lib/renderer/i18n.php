<?php
namespace MailPoet\Renderer;

class i18n extends \Twig_Extension {

  public function __construct() {
  }

  public function getName() {
    return 'i18n';
  }

  public function getFunctions() {
    // twig custom functions
    $twig_functions = array();
    // list of WP functions to map
    $functions = array('_', '__', '_e', '_c', '_n', '_x');

    foreach($functions as $function) {
      $twig_functions[] = new \Twig_SimpleFunction(
        $function,
        $function,
        array('is_safe' => array('all'))
      );
    }
    return $twig_functions;
  }
}
