<?php
namespace MailPoet\Renderer;

class i18n extends \Twig_Extension {

  public function __construct() {
  }

  public function getName() {
    return 'i18n';
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        '__',
        '__',
        array('is_safe' => array('all'))
      )
    );
  }
}