<?php
namespace MailPoet\WooCommerce;

class Helper {
  function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }
}
