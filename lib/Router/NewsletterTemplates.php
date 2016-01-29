<?php
namespace MailPoet\Router;

use MailPoet\Models\NewsletterTemplate;

if(!defined('ABSPATH')) exit;

class NewsletterTemplates {
  function __construct() {
  }

  function get($id = false) {
    $template = NewsletterTemplate::findOne($id);
    if($template === false) {
      return false;
    } else {
      $template->body = json_decode($template->body);
      return $template->asArray();
    }
  }

  function getAll() {
    $collection = NewsletterTemplate::findArray();
    $collection = array_map(function($item) {
      $item['body'] = json_decode($item['body']);
      return $item;
    }, $collection);
    return $collection;
  }

  function save($data = array()) {
    $result = NewsletterTemplate::createOrUpdate($data);
    if($result !== true) {
      return $result;
    } else {
      return true;
    }
  }

  function delete($id) {
    $template = NewsletterTemplate::findOne($id);
    if($template !== false) {
      $result = $template->delete();
    } else {
      $result = false;
    }
    return $result;
  }
}
