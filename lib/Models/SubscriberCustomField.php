<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SubscriberCustomField extends Model {
  public static $_table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;

  function __construct() {
    parent::__construct();
  }
}