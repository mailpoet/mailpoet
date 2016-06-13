<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Setting;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-includes/pluggable.php');

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
      $newsletter['options'] = Helpers::arrayColumn($options, 'value', 'name');
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

  function showPreview($data = array()) {
    if(!isset($data['body'])) {
      return array(
        'result' => false,
        'errors' => array(__('Newsletter data is missing.'))
      );
    }
    $newsletter_id = (isset($data['id'])) ? (int)$data['id'] : 0;
    $newsletter = Newsletter::findOne($newsletter_id);
    if(!$newsletter) {
      return array(
        'result' => false,
        'errors' => array(__('Newsletter could not be read.'))
      );
    }
    $newsletter->body = $data['body'];
    $newsletter->save();
    $wp_user =wp_get_current_user();
    $subscriber = Subscriber::where('email', $wp_user->data->user_email)
      ->findOne();
    $subscriber = ($subscriber) ? $subscriber->asArray() : $subscriber;
    $preview_url = NewsletterUrl::getViewInBrowserUrl($data, $subscriber);
    return array(
      'result' => true,
      'data' => array('url' => $preview_url)
    );
  }

  function sendPreview($data = array()) {
    $id = (isset($data['id'])) ? (int)$data['id'] : 0;
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return array(
        'result' => false
      );
    }
    if(empty($data['subscriber'])) {
      return array(
        'result' => false,
        'errors' => array(__('Please specify receiver information.'))
      );
    }

    $newsletter = $newsletter->asArray();

    $renderer = new Renderer($newsletter);
    $rendered_newsletter = $renderer->render();
    $divider = '***MailPoet***';
    $data_for_shortcodes =
      array_merge(array($newsletter['subject']), $rendered_newsletter);
    $body = implode($divider, $data_for_shortcodes);

    $wp_user = wp_get_current_user();
    $subscriber = Subscriber::where('email', $wp_user->data->user_email)
      ->findOne();
    $subscriber = ($subscriber) ? $subscriber->asArray() : $subscriber;

    $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $newsletter,
      $subscriber
    );
    list($newsletter['subject'],
      $newsletter['body']['html'],
      $newsletter['body']['text']
      ) = explode($divider, $shortcodes->replace($body));

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

    foreach($listing_data['items'] as $key => $newsletter) {
      $newsletter = $newsletter
        ->withSegments()
        ->withSendingQueue();
      if((boolean) Setting::getValue('tracking.enabled')) {
        $newsletter = $newsletter->withStatistics();
      }
      $listing_data['items'][$key] = $newsletter->asArray();
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
      if(!isset($data['id']) &&
        isset($data['type']) &&
        $data['type'] === 'notification'
      ) {
        Scheduler::processPostNotificationSchedule($newsletter->id);
      }
      return array(
        'result' => true,
        'newsletter' => $newsletter->asArray()
      );
    }
  }
}
