<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterPost extends Model {
  public static $_table = MP_NEWSLETTER_POSTS_TABLE;

  function __construct() {
    parent::__construct();
  }
}
