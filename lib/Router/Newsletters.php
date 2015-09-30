<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Mailer\Bridge;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Newsletter\Renderer\Renderer;

if(!defined('ABSPATH')) exit;

class Newsletters {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id'])) ? (int) $data['id'] : 0;
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      wp_send_json(false);
    } else {
      wp_send_json($newsletter->asArray());
    }
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

  function delete($id) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $result = $newsletter->delete();
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function send($id) {
    $newsletter = Newsletter::find_one($id)
      ->as_array();
    $subscribers = Subscriber::find_array();
    $mailer = new Bridge($newsletter, $subscribers);
    wp_send_json($mailer->send());
  }

  function render($data = array()) {
    if(!isset($data['body'])) {
      wp_send_json(false);
    }
    $renderer = new Renderer(json_decode($data['body'], true));
    wp_send_json(array('rendered_body' => $renderer->renderAll()));
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Newsletter'),
      $data
    );
    wp_send_json($listing->get());
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Newsletter',
      $data
    );
    wp_send_json($bulk_action->apply());
  }

  function create($data = array()) {
    $newsletter = Newsletter::create();
    $newsletter->type = $data['type'];
    $newsletter->body = '{}';

    // try to load template data
    $template = NewsletterTemplate::findOne((int)$data['template']);
    if($template !== false) {
      $newsletter->body = $template->body;
    }

    $result = $newsletter->save();
    if($result !== true) {
      wp_send_json($newsletter->getValidationErrors());
    } else {
      wp_send_json(array(
        'url' => admin_url(
          'admin.php?page=mailpoet-newsletter-editor&id='.$newsletter->id()
        )
      ));
    }
  }
}
