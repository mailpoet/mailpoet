<?php
namespace MailPoet\API\Endpoints;

use MailPoet\API\Endpoint as APIEndpoint;
use MailPoet\API\Error as APIError;
use MailPoet\Listing;
use MailPoet\Models\Setting;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Newsletters extends APIEndpoint {
  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      return $this->successResponse(
        $newsletter
        ->withSegments()
        ->withOptions()
        ->asArray()
      );
    }
  }

  function save($data = array()) {
    $segments = array();
    if(isset($data['segments'])) {
      $segments = $data['segments'];
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
      return $this->badRequest($errors);
    } else {
      if(!empty($segments)) {
        NewsletterSegment::where('newsletter_id', $newsletter->id)
          ->deleteMany();

        foreach($segments as $segment) {
          if(!is_array($segment)) continue;
          $relation = NewsletterSegment::create();
          $relation->segment_id = (int)$segment['id'];
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

      $newsletter = Newsletter::filter('filterWithOptions')->findOne($newsletter->id);

      // if this is a post notification, process options and update its schedule
      if($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        Scheduler::processPostNotificationSchedule($newsletter);
      }

      return $this->successResponse($newsletter->asArray());
    }
  }

  function setStatus($data = array()) {
    $status = (isset($data['status']) ? $data['status'] : null);

    if(!$status) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('You need to specify a status.', 'mailpoet')
      ));
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    }

    $newsletter->setStatus($status);
    $errors = $newsletter->getErrors();

    if(!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      return $this->successResponse(
        Newsletter::findOne($newsletter->id)->asArray()
      );
    }
  }

  function restore($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $newsletter->restore();
      return $this->successResponse(
        Newsletter::findOne($newsletter->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function trash($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $newsletter->trash();
      return $this->successResponse(
        Newsletter::findOne($newsletter->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $newsletter->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }

  function duplicate($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $data = array(
        'subject' => sprintf(__('Copy of %s', 'mailpoet'), $newsletter->subject)
      );
      $duplicate = $newsletter->duplicate($data);
      $errors = $duplicate->getErrors();

      if(!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        return $this->successResponse(
          Newsletter::findOne($duplicate->id)->asArray(),
          array('count' => 1)
        );
      }
    }
  }

  function showPreview($data = array()) {
    if(empty($data['body'])) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Newsletter data is missing.', 'mailpoet')
      ));
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $newsletter->body = $data['body'];
      $newsletter->save();
      $subscriber = Subscriber::getCurrentWPUser();
      $preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        $subscriber
      );

      return $this->successResponse(
        Newsletter::findOne($newsletter->id)->asArray(),
        array('preview_url' => $preview_url)
      );
    }
  }

  function sendPreview($data = array()) {
    if(empty($data['subscriber'])) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Please specify receiver information.', 'mailpoet')
      ));
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $renderer = new Renderer($newsletter, $preview = true);
      $rendered_newsletter = $renderer->render();
      $divider = '***MailPoet***';
      $data_for_shortcodes = array_merge(
        array($newsletter->subject),
        $rendered_newsletter
      );

      $body = implode($divider, $data_for_shortcodes);

      $subscriber = Subscriber::getCurrentWPUser();
      $subscriber = ($subscriber) ? $subscriber : false;

      $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
        $newsletter,
        $subscriber
      );

      list(
        $rendered_newsletter['subject'],
        $rendered_newsletter['body']['html'],
        $rendered_newsletter['body']['text']
      ) = explode($divider, $shortcodes->replace($body));

      try {
        $mailer = (!empty($data['mailer'])) ?
          $data['mailer'] :
          new \MailPoet\Mailer\Mailer(
            $mailer = false,
            $sender = false,
            $reply_to = false
        );
        $result = $mailer->send($rendered_newsletter, $data['subscriber']);

        if($result['response'] === false) {
          $error = sprintf(
            __('The email could not be sent: %s', 'mailpoet'),
            $result['error']
          );
          return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
        } else {
          return $this->successResponse(
            Newsletter::findOne($id)->asArray()
          );
        }
      } catch(\Exception $e) {
        return $this->errorResponse(array(
          $e->getCode() => $e->getMessage()
        ));
      }
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Newsletter',
      $data
    );
    $listing_data = $listing->get();

    $data = array();
    foreach($listing_data['items'] as $newsletter) {
      $queue = false;

      if($newsletter->type === Newsletter::TYPE_STANDARD) {
        $newsletter
          ->withSegments(true)
          ->withSendingQueue()
          ->withStatistics();
      } else if($newsletter->type === Newsletter::TYPE_WELCOME) {
        $newsletter
          ->withOptions()
          ->withTotalSent()
          ->withStatistics();
      } else if($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        $newsletter
          ->withOptions()
          ->withSegments(true)
          ->withChildrenCount();
      } else if($newsletter->type === Newsletter::TYPE_NOTIFICATION_HISTORY) {
        $newsletter
          ->withSegments(true)
          ->withSendingQueue()
          ->withStatistics();
      }

      if($newsletter->status === Newsletter::STATUS_SENT ||
         $newsletter->status === Newsletter::STATUS_SENDING
      ) {
        $queue = $newsletter->getQueue();
      }

      // get preview url
      $subscriber = Subscriber::getCurrentWPUser();
      $newsletter->preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        $subscriber,
        $queue
      );

      $data[] = $newsletter->asArray();
    }

    return $this->successResponse($data, array(
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
      'mta_log' => Setting::getValue('mta_log'),
      'mta_method' => Setting::getValue('mta.method')
    ));
  }

  function bulkAction($data = array()) {
    try {
      $bulk_action = new Listing\BulkAction(
        '\MailPoet\Models\Newsletter',
        $data
      );
      $meta = $bulk_action->apply();
      return $this->successResponse(null, $meta);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }

  function create($data = array()) {
    $options = array();
    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if(!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      // try to load template data
      $template_id = (isset($data['template']) ? (int)$data['template'] : false);
      $template = NewsletterTemplate::findOne($template_id);
      if($template === false) {
        $newsletter->body = array();
      } else {
        $newsletter->body = $template->body;
      }
    }

    $newsletter->save();
    $errors = $newsletter->getErrors();
    if(!empty($errors)) {
      return $this->badRequest($errors);
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

      if(
        empty($data['id'])
        &&
        isset($data['type'])
        &&
        $data['type'] === Newsletter::TYPE_NOTIFICATION
      ) {
        $newsletter = Newsletter::filter('filterWithOptions')->findOne($newsletter->id);
        Scheduler::processPostNotificationSchedule($newsletter);
      }

      return $this->successResponse(
        Newsletter::findOne($newsletter->id)->asArray()
      );
    }
  }
}
