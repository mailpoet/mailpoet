<?php
namespace MailPoet\API\Endpoints;

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
    $template = NewsletterTemplate::createOrUpdate($data);
    return ($template->getErrors() === false && $template->id() > 0);
  }

  function delete($id) {
    $template = NewsletterTemplate::findOne($id);
    if($template !== false) {
      return $template->delete();
    } else {
      return false;
    }
  }
}
