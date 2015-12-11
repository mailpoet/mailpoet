<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Mailer\API\MailPoet;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterOption;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Models\SendingQueue;

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
      $segments = $newsletter->segments()->findArray();
      $options = $newsletter->options()->findArray();
      $newsletter = $newsletter->asArray();
      $newsletter['segments'] = array_map(function($segment) {
        return $segment['id'];
      }, $segments);
      $newsletter['options'] = $options;

      wp_send_json($newsletter);
    }
  }

  function getAll() {
    $collection = Newsletter::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    if(isset($data['segments'])) {
      $segment_ids = $data['segments'];
      unset($data['segments']);
    }

    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $errors = array();
    $result = false;

    $newsletter = Newsletter::createOrUpdate($data);

    if($newsletter !== false && !$newsletter->id()) {
      $errors = $newsletter->getValidationErrors();
    } else {
      $result = true;

      if(!empty($segment_ids)) {
        NewsletterSegment::where('newsletter_id', $newsletter->id)
          ->deleteMany();

        foreach($segment_ids as $segment_id) {
          $relation = NewsletterSegment::create();
          $relation->segment_id = $segment_id;
          $relation->newsletter_id = $newsletter->id;
          $relation->save();
        }
      }

      if(!empty($options)) {
        NewsletterOption::where('newsletter_id', $newsletter->id)
          ->deleteMany();

        $optionFields = NewsletterOptionField::where(
          'newsletter_type',
          $data['type']
        )->findArray();

        foreach($optionFields as $optionField) {
          if(isset($options[$optionField['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $optionField['id'];
            $relation->value = $options[$optionField['name']];
            $relation->save();
          }
        }
      }
    }
    wp_send_json(array(
      'result' => $result,
      'errors' => $errors
    ));
  }

  function restore($id) {
    $result = false;

    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $result = $newsletter->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $result = $newsletter->trash();
    }

    wp_send_json($result);
  }

  function delete($id) {
    $result = false;

    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $newsletter->delete();
      $result = 1;
    }

    wp_send_json($result);
  }

  function duplicate($id) {
    $result = false;

    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $data = array(
        'subject' => sprintf(__('Copy of %s'), $newsletter->subject)
      );
      $result = $newsletter->duplicate($data)->asArray();
    }

    wp_send_json($result);
  }

  function send($data = array()) {
    $newsletter = Newsletter::findOne($data['id'])->asArray();

    if(empty($data['segments'])) {
      return wp_send_json(array(
        'errors' => array(
            __("You need to select a list.")
          )
      ));
    }

    $segments = Segment::whereIdIn($data['segments'])->findMany();
    $subscribers = array();
    foreach($segments as $segment) {
      $segment_subscribers = $segment->subscribers()->findMany();
      foreach($segment_subscribers as $segment_subscriber) {
        $subscribers[$segment_subscriber->email] = $segment_subscriber
          ->asArray();
      }
    }

    if(empty($subscribers)) {
      return wp_send_json(array(
        'errors' => array(
            __("No subscribers found.")
          )
      ));
    }

    // TO REMOVE once we add the columns from/reply_to
    $newsletter = array_merge($newsletter, $data['newsletter']);
    // END - TO REMOVE

    $renderer = new Renderer(json_decode($newsletter['body'], true));
    $newsletter['body']['html'] = $renderer->renderAll();
    $newsletter['body']['text'] = '';

    $subscribers = Subscriber::find_array();
    $fromEmail = Setting::where('name', 'from_address')->findOne()->value;
    $fromName = Setting::where('name', 'from_name')->findOne()->value;
    $apiKey = Setting::where('name', 'api_key')->findOne()->value;
    $mailer = new MailPoet($apiKey, $fromEmail, $fromName);

    foreach ($subscribers as $subscriber) {
      $result = $mailer->send(
        $newsletter,
        sprintf('%s %s <%s>', $subscriber['first_name'], $subscriber['last_name'], $subscriber['email'])
      );
      if ($result !== true) wp_send_json(false);
    }

    wp_send_json(true);
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
      '\MailPoet\Models\Newsletter',
      $data
    );

    $listing_data = $listing->get();

    foreach($listing_data['items'] as &$item) {
      // get segments
      $segments = NewsletterSegment::select('segment_id')
        ->where('newsletter_id', $item['id'])
        ->findMany();
      $item['segments'] = array_map(function($relation) {
        return $relation->segment_id;
      }, $segments);

      // get queue
      $queue = SendingQueue::where('newsletter_id', $item['id'])
        ->orderByDesc('updated_at')
        ->findOne();
      $item['queue'] = ($queue !== false) ? $queue->asArray() : null;
    }

    wp_send_json($listing_data);
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Newsletter',
      $data
    );
    wp_send_json($bulk_action->apply());
  }

  function create($data = array()) {
    $newsletter = Newsletter::create();
    $newsletter->type = $data['type'];
    $newsletter->subject = $data['subject'];
    $newsletter->body = '{}';

    // try to load template data
    $template_id = (!empty($data['template']) ? (int)$data['template'] : 0);
    $template = NewsletterTemplate::findOne($template_id);
    if($template !== false) {
      $newsletter->body = $template->body;
    }

    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $result = $newsletter->save();
    if($result !== true) {
      wp_send_json($newsletter->getValidationErrors());
    } else {
      if(!empty($options)) {
        $optionFields = NewsletterOptionField::where('newsletter_type', $newsletter->type)->findArray();

        foreach($optionFields as $optionField) {
          if(isset($options[$optionField['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $optionField['id'];
            $relation->value = $options[$optionField['name']];
            $relation->save();
          }
        }
      }
      wp_send_json($newsletter->asArray());
    }
  }
}
