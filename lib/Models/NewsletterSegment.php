<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterSegment extends Model {
  public static $_table = MP_NEWSLETTER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }
}
