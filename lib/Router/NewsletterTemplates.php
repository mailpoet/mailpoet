<?php
namespace MailPoet\Router;

use MailPoet\Models\NewsletterTemplate;

if(!defined('ABSPATH')) exit;

class NewsletterTemplates {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id'])) ? (int) $data['id'] : 0;
    $template = NewsletterTemplate::findOne($id);
    if($template === false) {
      wp_send_json(false);
    } else {
      wp_send_json($template->asArray());
    }
  }

  function getAll() {
    $collection = NewsletterTemplate::find_array();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = NewsletterTemplate::createOrUpdate($data);
    if($result !== true) {
      wp_send_json($result);
    } else {
      wp_send_json(true);
    }
  }

  function delete($id) {
    $template = NewsletterTemplate::findOne($id);
    if($template !== false) {
      $result = $template->delete();
    } else {
      $result = false;
    }
    wp_send_json($result);
  }
}
