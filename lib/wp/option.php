<?php
namespace MailPoet\WP;

class Option {
  function get($name) {
    return get_option($name);
  }

  function set($name, $value) {
    return update_option($name, $value);
  }
}
