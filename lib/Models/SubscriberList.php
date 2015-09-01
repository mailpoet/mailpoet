<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SubscriberList extends Model {
  public static $_table = MP_SUBSCRIBER_LIST_TABLE;

  function __construct() {
    parent::__construct();
  }
}
