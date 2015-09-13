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

  function save($args) {
    $model = Newsletter::create();
    $model->hydrate($args);
    $result = $model->save();
    wp_send_json($result);
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
