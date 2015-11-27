<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterStatistics extends Model {
  public static $_table = MP_NEWSLETTER_STATISTICS_TABLE;

  function __construct() {
    parent::__construct();
  }
}