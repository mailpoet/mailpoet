<?php namespace MailPoet\Models;
if (!defined('ABSPATH')) exit;

class Subscriber {

  public $name;

  public function __construct () {
    $this->name = 'First Subscriber';
  }
}
