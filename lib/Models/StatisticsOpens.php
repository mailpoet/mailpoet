<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class StatisticsOpens extends Model {
  public static $_table = MP_STATISTICS_OPENS_TABLE;

  function __construct() {
    parent::__construct();
  }
}