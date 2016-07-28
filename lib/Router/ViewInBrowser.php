<?php
namespace MailPoet\Router;

use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;

if(!defined('ABSPATH')) exit;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';

  static function view($data) {
    $viewer = new NewsletterViewInBrowser($data);
    $viewer->view();
  }
}