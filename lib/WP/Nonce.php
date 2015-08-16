<?php
namespace MailPoet\WP;

class Nonce {
  static function check($action = 'mailpoet') {
    if(wp_verify_nonce($_GET['mailpoet_nonce'], $action) === false) {
      wp_die();
    }
  }
}