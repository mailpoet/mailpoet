<?php
namespace MailPoet\Router;
use \MailPoet\Models\Newsletter;
use \MailPoet\Models\Subscriber;
use \MailPoet\Mailer\Bridge;
use \MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Newsletters {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : 0);

    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      wp_send_json(false);
    } else {
      wp_send_json($newsletter->asArray());
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Newsletter'),
      $data
    );
    wp_send_json($listing->get());
  }

  function getAll() {
    $collection = Newsletter::find_array();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = Newsletter::createOrUpdate($data);

    if($result !== true) {
      wp_send_json($result);
    } else {
      wp_send_json(true);
    }
  }

  function update($args) {

  }

  function delete($id) {

  }

  function send($id) {
    $newsletter = Newsletter::find_one($id)->as_array();
    $subscribers = Subscriber::find_array();
    $mailer = new Bridge($newsletter, $subscribers);
    wp_send_json($mailer->send());
  }
}
