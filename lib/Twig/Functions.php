<?php
namespace MailPoet\Twig;

class Functions extends \Twig_Extension {

  public function __construct() {
  }

  public function getName() {
    return 'functions';
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'json_encode',
        'json_encode',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'json_decode',
        'json_decode',
        array('is_safe' => array('all'))
      )
    );
  }
}
