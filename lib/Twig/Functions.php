<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Functions extends \Twig_Extension {

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
        'get_option',
        'get_option',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'sending_frequency',
        array($this, 'getSendingFrequency'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_date_format',
        array($this, 'getWPDateFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_time_format',
        array($this, 'getWPTimeFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_datetime_format',
        array($this, 'getWPDateTimeFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'do_action',
        'do_action',
        array('is_safe' => array('all'))
      )
    );
  }

  function getSendingFrequency() {
    $args = func_get_args();
    $value = (int)array_shift($args);

    $label = null;
    $labels = array(
      'minute' => __('every minute', 'mailpoet'),
      'minutes' => __('every %1$d minutes', 'mailpoet'),
      'hour' => __('every hour', 'mailpoet'),
      'hours' => __('every %1$d hours', 'mailpoet')
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

  function getWPDateFormat() {
    return (get_option('date_format')) ?
      get_option('date_format') :
      'F j, Y';
  }

  function getWPTimeFormat() {
    return (get_option('time_format')) ?
      get_option('time_format') :
      'g:i a';
  }

  function getWPDateTimeFormat() {
    return sprintf('%s %s', $this->getWPDateFormat(), $this->getWPTimeFormat());
  }

  function params($key = null) {
    $args = stripslashes_deep($_GET);
    if(array_key_exists($key, $args)) {
      return $args[$key];
    }
    return null;
  }
}