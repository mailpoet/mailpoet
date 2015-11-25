<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Queue extends Model {
  public static $_table = MP_QUEUES_TABLE;

  function __construct() {
    parent::__construct();
  }
}