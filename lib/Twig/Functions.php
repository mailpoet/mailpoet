<?php
namespace MailPoet\Twig;

class Functions extends \Twig_Extension {

  function __construct() {
  }

  function getName() {
    return 'functions';
  }

  function getFunctions() {
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
      ),
      new \Twig_SimpleFunction(
        'wp_nonce_field',
        'wp_nonce_field',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'params',
        array($this, 'params'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'admin_url',
        'admin_url',
        array('is_safe' => array('all'))
      )
    );
  }

  function params($key = null) {
    $args = stripslashes_deep($_GET);
    if(array_key_exists($key, $args)) {
      return $args[$key];
    }
    return null;
  }
}
