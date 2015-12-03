<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUE_TABLE;

  function __construct() {
    parent::__construct();
  }
}