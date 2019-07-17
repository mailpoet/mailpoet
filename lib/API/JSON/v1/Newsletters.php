<?php

namespace MailPoet\API\JSON\v1;

use Carbon\Carbon;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterQueueTask;
use MailPoet\Listing;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WooCommerce\Helper as WCHelper;

if (!defined('ABSPATH')) exit;

class Newsletters extends APIEndpoint {

  /** @var Listing\BulkActionController */
  private $bulk_action;

  /** @var Listing\Handler */
  private $listing_handler;

  /** @var WPFunctions */
  private $wp;

  /** @var WCHelper */
  private $woocommerce_helper;

  /** @var SettingsController */
  private $settings;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  function __construct(
    Listing\BulkActionController $bulk_action,
    Listing\Handler $listing_handler,
    WPFunctions $wp,
    WCHelper $woocommerce_helper,
    SettingsController $settings,
    AuthorizedEmailsController $authorized_emails_controller
  ) {
    $this->bulk_action = $bulk_action;
    $this->listing_handler = $listing_handler;
    $this->wp = $wp;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->settings = $settings;
    $this->authorized_emails_controller = $authorized_emails_controller;
  }

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter = $newsletter
        ->withSegments()
        ->withOptions()
        ->withSendingQueue();

      $preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        Subscriber::getCurrentWPUser()
      );

      $newsletter = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $newsletter->asArray());
      return $this->successResponse($newsletter, ['preview_url' => $preview_url]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function getWithStats($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter = $newsletter
        ->withSegments()
        ->withOptions()
        ->withSendingQueue()
        ->withTotalSent()
        ->withStatistics($this->woocommerce_helper);

      $preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        Subscriber::getCurrentWPUser()
      );

      $newsletter = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $newsletter->asArray());
      return $this->successResponse($newsletter, ['preview_url' => $preview_url]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function save($data = []) {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);

    $segments = [];
    if (isset($data['segments'])) {
      $segments = $data['segments'];
      unset($data['segments']);
    }

    $options = [];
    if (isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    if (!empty($data['template_id'])) {
      $template = NewsletterTemplate::whereEqual('id', $data['template_id'])->findOne();
      if ($template instanceof NewsletterTemplate) {
        $template = $template->asArray();
        $data['body'] = $template['body'];
      }
      unset($data['template_id']);
    }

    $old_newsletter = null;
    if (isset($data['id'])) {
      $old_newsletter = Newsletter::findOne(intval($data['id'])) ?: null;
    }

    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) return $this->badRequest($errors);
    // Re-fetch newsletter to sync changes made by DB
    // updated_at column use CURRENT_TIMESTAMP for update and this change is not updated automatically by ORM
    $newsletter = Newsletter::findOne($newsletter->id);
    if(!$newsletter instanceof Newsletter) return $this->errorResponse();

    if (!empty($segments)) {
      NewsletterSegment::where('newsletter_id', $newsletter->id)
        ->deleteMany();
      foreach ($segments as $segment) {
        if (!is_array($segment)) continue;
        $relation = NewsletterSegment::create();
        $relation->segment_id = (int)$segment['id'];
        $relation->newsletter_id = $newsletter->id;
        $relation->save();
      }
    }

    if (isset($data['sender_address']) && isset($data['sender_name'])) {
      Setting::saveDefaultSenderIfNeeded($data['sender_address'], $data['sender_name']);
    }

    if (!empty($options)) {
      $option_fields = NewsletterOptionField::where(
        'newsletter_type',
        $newsletter->type
      )->findMany();
      // update newsletter options
      foreach ($option_fields as $option_field) {
        if (isset($options[$option_field->name])) {
          $newsletter_option = NewsletterOption::createOrUpdate(
            [
              'newsletter_id' => $newsletter->id,
              'option_field_id' => $option_field->id,
              'value' => $options[$option_field->name],
            ]
          );
        }
      }
      // reload newsletter with updated options
      $newsletter = Newsletter::filter('filterWithOptions', $newsletter->type)->findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      // if this is a post notification, process newsletter options and update its schedule
      if ($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        // generate the new schedule from options and get the new "next run" date
        $newsletter->schedule = Scheduler::processPostNotificationSchedule($newsletter);
        $next_run_date = Scheduler::getNextRunDate($newsletter->schedule);
        // find previously scheduled jobs and reschedule them using the new "next run" date
        SendingQueue::findTaskByNewsletterId($newsletter->id)
          ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $next_run_date)
          ->save();
      }
    }

    $queue = $newsletter->getQueue();
    if ($queue && !in_array($newsletter->type, [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_NOTIFICATION_HISTORY])) {
      // if newsletter was previously scheduled and is now unscheduled, set its status to DRAFT and delete associated queue record
      if ($newsletter->status === Newsletter::STATUS_SCHEDULED && isset($options['isScheduled']) && empty($options['isScheduled'])) {
        $queue->delete();
        $newsletter->status = Newsletter::STATUS_DRAFT;
        $newsletter->save();
      } else {
        $queue->newsletter_rendered_body = null;
        $queue->newsletter_rendered_subject = null;
        $newsletterQueueTask = new NewsletterQueueTask();
        $newsletterQueueTask->preProcessNewsletter($newsletter, $queue);
      }
    }

    $this->wp->doAction('mailpoet_api_newsletters_save_after', $newsletter);
    $this->authorized_emails_controller->onNewsletterUpdate($newsletter, $old_newsletter);

    $preview_url = NewsletterUrl::getViewInBrowserUrl(
      NewsletterUrl::TYPE_LISTING_EDITOR,
      $newsletter,
      Subscriber::getCurrentWPUser()
    );

    return $this->successResponse($newsletter->asArray(), ['preview_url' => $preview_url]);
  }

  function setStatus($data = []) {
    $status = (isset($data['status']) ? $data['status'] : null);

    if (!$status) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => WPFunctions::get()->__('You need to specify a status.', 'mailpoet'),
      ]);
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOneWithOptions($id);

    if ($newsletter === false) {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newsletter->setStatus($status);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) {
      return $this->errorResponse($errors);
    }

    // if there are past due notifications, reschedule them for the next send date
    if ($newsletter->type === Newsletter::TYPE_NOTIFICATION && $status === Newsletter::STATUS_ACTIVE) {
      $next_run_date = Scheduler::getNextRunDate($newsletter->schedule);
      $queue = $newsletter->queue()->findOne();
      if ($queue) {
        $queue->task()
          ->whereLte('scheduled_at', Carbon::createFromTimestamp($this->wp->currentTime('timestamp')))
          ->where('status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $next_run_date)
          ->save();
      }
      Scheduler::createPostNotificationSendingTask($newsletter);
    }

    $newsletter = Newsletter::findOne($newsletter->id);
    if(!$newsletter instanceof Newsletter) return $this->errorResponse();
    return $this->successResponse(
      $newsletter->asArray()
    );
  }

  function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->restore();

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();

      return $this->successResponse(
        $newsletter->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->trash();

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      return $this->successResponse(
        $newsletter->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function duplicate($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);

    if ($newsletter instanceof Newsletter) {
      $data = [
        'subject' => sprintf(__('Copy of %s', 'mailpoet'), $newsletter->subject),
      ];
      $duplicate = $newsletter->duplicate($data);
      $errors = $duplicate->getErrors();

      if (!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        $this->wp->doAction('mailpoet_api_newsletters_duplicate_after', $newsletter, $duplicate);
        $duplicate = Newsletter::findOne($duplicate->id);
        if(!$duplicate instanceof Newsletter) return $this->errorResponse();
        return $this->successResponse(
          $duplicate->asArray(),
          ['count' => 1]
        );
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function showPreview($data = []) {
    if (empty($data['body'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Newsletter data is missing.', 'mailpoet'),
      ]);
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);

    if ($newsletter instanceof Newsletter) {
      $newsletter->body = $data['body'];
      $newsletter->save();
      $subscriber = Subscriber::getCurrentWPUser();
      $preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        $subscriber
      );
      // strip protocol to avoid mix content error
      $preview_url = preg_replace('{^https?:}i', '', $preview_url);

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      return $this->successResponse(
        $newsletter->asArray(),
        ['preview_url' => $preview_url]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function sendPreview($data = []) {
    if (empty($data['subscriber'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please specify receiver information.', 'mailpoet'),
      ]);
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);

    if ($newsletter instanceof Newsletter) {
      $renderer = new Renderer($newsletter, $preview = true);
      $rendered_newsletter = $renderer->render();
      $divider = '***MailPoet***';
      $data_for_shortcodes = array_merge(
        [$newsletter->subject],
        $rendered_newsletter
      );

      $body = implode($divider, $data_for_shortcodes);

      $subscriber = Subscriber::getCurrentWPUser();
      $subscriber = ($subscriber) ? $subscriber : false;

      $shortcodes = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
        $newsletter,
        $subscriber,
        $queue = false,
        $wp_user_preview = true
      );

      list(
        $rendered_newsletter['subject'],
        $rendered_newsletter['body']['html'],
        $rendered_newsletter['body']['text']
        ) = explode($divider, $shortcodes->replace($body));
      $rendered_newsletter['id'] = $newsletter->id;

      try {
        $mailer = (!empty($data['mailer'])) ?
          $data['mailer'] :
          new \MailPoet\Mailer\Mailer();
        $extra_params = ['unsubscribe_url' => WPFunctions::get()->homeUrl()];
        $result = $mailer->send($rendered_newsletter, $data['subscriber'], $extra_params);

        if ($result['response'] === false) {
          $error = sprintf(
            WPFunctions::get()->__('The email could not be sent: %s', 'mailpoet'),
            $result['error']->getMessage()
          );
          return $this->errorResponse([APIError::BAD_REQUEST => $error]);
        } else {
          $newsletter = Newsletter::findOne($newsletter->id);
          if(!$newsletter instanceof Newsletter) return $this->errorResponse();

          return $this->successResponse(
            $newsletter->asArray()
          );
        }
      } catch (\Exception $e) {
        return $this->errorResponse([
          $e->getCode() => $e->getMessage(),
        ]);
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  function listing($data = []) {
    $listing_data = $this->listing_handler->get('\MailPoet\Models\Newsletter', $data);

    $data = [];
    foreach ($listing_data['items'] as $newsletter) {
      $queue = false;

      if ($newsletter->type === Newsletter::TYPE_STANDARD) {
        $newsletter
          ->withSegments(true)
          ->withSendingQueue()
          ->withStatistics($this->woocommerce_helper);
      } else if ($newsletter->type === Newsletter::TYPE_WELCOME || $newsletter->type === Newsletter::TYPE_AUTOMATIC) {
        $newsletter
          ->withOptions()
          ->withTotalSent()
          ->withScheduledToBeSent()
          ->withStatistics($this->woocommerce_helper);
      } else if ($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        $newsletter
          ->withOptions()
          ->withSegments(true)
          ->withChildrenCount();
      } else if ($newsletter->type === Newsletter::TYPE_NOTIFICATION_HISTORY) {
        $newsletter
          ->withSegments(true)
          ->withSendingQueue()
          ->withStatistics($this->woocommerce_helper);
      }

      if ($newsletter->status === Newsletter::STATUS_SENT ||
         $newsletter->status === Newsletter::STATUS_SENDING
      ) {
        $queue = $newsletter->getQueue();
      }

      // get preview url
      $newsletter->preview_url = NewsletterUrl::getViewInBrowserUrl(
        NewsletterUrl::TYPE_LISTING_EDITOR,
        $newsletter,
        $subscriber = null,
        $queue
      );

      $data[] = $this->wp->applyFilters('mailpoet_api_newsletters_listing_item', $newsletter->asArray());
    }

    return $this->successResponse($data, [
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => CronHelper::isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql'),
    ]);
  }

  function bulkAction($data = []) {
    try {
      $meta = $this->bulk_action->apply('\MailPoet\Models\Newsletter', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  function create($data = []) {
    $options = [];
    if (isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      // try to load template data
      $template_id = (isset($data['template']) ? (int)$data['template'] : false);
      $template = NewsletterTemplate::findOne($template_id);
      if ($template instanceof NewsletterTemplate) {
        $newsletter->body = $template->body;
      } else {
        $newsletter->body = [];
      }
    }

    $newsletter->save();
    $errors = $newsletter->getErrors();
    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      if (!empty($options)) {
        $option_fields = NewsletterOptionField::where(
          'newsletter_type', $newsletter->type
        )->findArray();

        foreach ($option_fields as $option_field) {
          if (isset($options[$option_field['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $option_field['id'];
            $relation->value = $options[$option_field['name']];
            $relation->save();
          }
        }
      }

      if (
        empty($data['id'])
        &&
        isset($data['type'])
        &&
        $data['type'] === Newsletter::TYPE_NOTIFICATION
      ) {
        $newsletter = Newsletter::filter('filterWithOptions', $data['type'])->findOne($newsletter->id);
        Scheduler::processPostNotificationSchedule($newsletter);
      }

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      return $this->successResponse(
        $newsletter->asArray()
      );
    }
  }
}
