<?php
namespace MailPoet\Router;

use MailPoet\Config\Shortcodes;
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

  function get($id = false) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      return false;
    } else {
      $segments = $newsletter->segments()->findArray();
      $options = $newsletter->options()->findArray();
      $newsletter = $newsletter->asArray();
      $newsletter['segments'] = array_map(function($segment) {
        return $segment['id'];
      }, $segments);
      $newsletter['options'] = $options;
      return $newsletter;
    }
  }

  function save($data = array()) {
    $segment_ids = array();
    if(isset($data['segments'])) {
      $segment_ids = $data['segments'];
      unset($data['segments']);
    }

    $options = array();
    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
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

        $option_fields = NewsletterOptionField::where(
          'newsletter_type',
          $data['type']
        )->findArray();

        foreach($option_fields as $option_field) {
          if(isset($options[$option_field['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $option_field['id'];
            $relation->value = $options[$option_field['name']];
            $relation->save();
          }
        }
      }

      return array(
        'result' => true
      );
    }
  }

  function restore($id) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $newsletter->restore();
    }
    return ($newsletter->getErrors() === false);
  }

  function trash($id) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $newsletter->trash();
    }
    return ($newsletter->getErrors() === false);
  }

  function delete($id) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      $newsletter->delete();
      return 1;
    }
    return false;
  }

  function duplicate($id = false) {
    $newsletter = Newsletter::findOne($id);
    if($newsletter !== false) {
      return $newsletter->duplicate(array(
        'subject' => sprintf(__('Copy of %s'), $newsletter->subject)
      ))->asArray();
    }
    return false;
  }

  function render($data = array()) {
    if(!isset($data['body'])) {
      return false;
    }
    $renderer = new Renderer($data);
    $rendered_newsletter = $renderer->render();
    $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $rendered_newsletter['html'],
      $data
    );
    $rendered_newsletter = $shortcodes->replace();
    return array('rendered_body' => $rendered_newsletter);
  }

  function sendPreview($data = array()) {
    $id = (isset($data['id'])) ? (int) $data['id'] : 0;
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return array(
        'result' => false
      );
    }
    if(empty($data['subscriber'])) {
      return array(
        'result' => false,
        'errors' => array(__('Please specify receiver information'))
      );
    }

    $newsletter = $newsletter->asArray();

    $renderer = new Renderer($newsletter);
    $rendered_newsletter = $renderer->render();
    $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $rendered_newsletter['html'],
      $newsletter
    );
    $processed_newsletter['html'] = $shortcodes->replace();
    $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $rendered_newsletter['text'],
      $newsletter
    );
    $processed_newsletter['text'] = $shortcodes->replace();
    $newsletter['body'] = array(
      'html' => $processed_newsletter['html'],
      'text' => $processed_newsletter['text'],
    );

    try {
      $mailer = new \MailPoet\Mailer\Mailer(
        $mailer = false,
        $sender = false,
        $reply_to = false
      );
      $result = $mailer->send($newsletter, $data['subscriber']);

      return array('result' => $result);
    } catch(\Exception $e) {
      return array(
        'result' => false,
        'errors' => array($e->getMessage()),
      );
    }
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

    return $listing_data;
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Newsletter',
      $data
    );
    return $bulk_action->apply();
  }

  function create($data = array()) {
    $options = array();
    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $newsletter = Newsletter::createOrUpdate($data);

    // try to load template data
    $template_id = (!empty($data['template']) ? (int)$data['template'] : false);
    $template = NewsletterTemplate::findOne($template_id);
    if($template !== false) {
      $newsletter->body = $template->body;
    } else {
      $newsletter->body = array();
    }

    $newsletter->save();
    $errors = $newsletter->getErrors();
    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' =>$errors
      );
    } else {
      if(!empty($options)) {
        $option_fields = NewsletterOptionField::where(
          'newsletter_type', $newsletter->type
        )->findArray();

        foreach($option_fields as $option_field) {
          if(isset($options[$option_field['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $option_field['id'];
            $relation->value = $options[$option_field['name']];
            $relation->save();
          }
        }
      }
      return array(
        'result' => true,
        'newsletter' => $newsletter->asArray()
      );
    }
  }
}