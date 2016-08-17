<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;

if(!defined('ABSPATH')) exit;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';

  static function view($data) {
    NewsletterViewInBrowser::view($data);
  }
}