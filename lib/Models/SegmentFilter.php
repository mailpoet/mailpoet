<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SegmentFilter extends Model {
  public static $_table = MP_SEGMENT_FILTER_TABLE;

  function __construct() {
    parent::__construct();
  }
}
