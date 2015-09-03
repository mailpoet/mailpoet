<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }
}
