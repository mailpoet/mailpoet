<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class RelationSubscriberList extends Model {
  public static $_table = MP_PIVOT_SUBSCRIBER_LIST_TABLE;

  function __construct() {
    parent::__construct();
  }
}
