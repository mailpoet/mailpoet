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
      ),
      new \Twig_SimpleFunction(
        'get_option',
        'get_option',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'sending_frequency',
        array($this, 'getSendingFrequency'),
        array('is_safe' => array('all'))
      ),

    );
  }

  function getSendingFrequency() {
    $args = func_get_args();
    $value = (int)array_shift($args);

    $label = null;
    $labels = array(
      'minute' => __('every minute'),
      'minutes' => __('every %1$d minutes'),
      'hour' => __('every hour'),
      'hours' => __('every %1$d hours')
    );

    if($value >= 60) {
      // we're dealing with hours
      if($value === 60) {
        $label = $labels['hour'];
      } else {
        $label = $labels['hours'];
      }
      $value /= 60;
    } else {
      // we're dealing with minutes
      if($value === 1) {
        $label = $labels['minute'];
      } else {
        $label = $labels['minutes'];
      }
    }

    if($label !== null) {
      return sprintf($label, $value);
    } else {
      return $value;
    }
  }

  function params($key = null) {
    $args = stripslashes_deep($_GET);
    if(array_key_exists($key, $args)) {
      return $args[$key];
    }
    return null;
  }
}
