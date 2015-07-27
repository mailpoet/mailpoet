<?php
// Bypass WP protection of classes in unit tests.
define( 'ABSPATH', '');

if(!defined('__')) {
  function __($value) {
    return $value;
  }
}