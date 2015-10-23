<?php
namespace MailPoet\Router;

use MailPoet\Import\MailChimp;

if(!defined('ABSPATH')) exit;

class Import {
  function getMailChimpLists($data) {
    $mailChimp = new MailChimp($data['api_key']);
    wp_send_json($mailChimp->getLists());
  }
}
