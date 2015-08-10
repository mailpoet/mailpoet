<?php
namespace MailPoet\WP;

class Option {

  function __construct() {
    $this->prefix = 'mailpoet_';
  }

  function get($name) {
    return get_option($this->prefix . $name);
  }

  function set($name, $value) {
    return update_option($this->prefix .$name, $value);
  }
}
