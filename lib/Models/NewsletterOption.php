<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterOption extends Model {
  public static $_table = MP_NEWSLETTER_OPTIONS_TABLE;

  function __construct() {
    parent::__construct();
  }
}
