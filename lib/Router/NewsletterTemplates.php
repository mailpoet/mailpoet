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
      return $template->asArray();
    }
  }

  function getAll() {
    $collection = NewsletterTemplate::findMany();
    return array_map(function($item) {
      return $item->asArray();
    }, $collection);
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
