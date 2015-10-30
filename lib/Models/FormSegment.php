<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class FormSegment extends Model {
  public static $_table = MP_FORM_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }
}
