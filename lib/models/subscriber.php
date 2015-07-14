<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Subscriber extends Model {

  public $name;

  public function __construct () {
    $this->name = 'First Subscriber';
  }
}
