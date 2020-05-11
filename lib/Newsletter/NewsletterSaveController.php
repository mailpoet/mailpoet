<?php

namespace MailPoet\Newsletter;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterQueueTask;
use MailPoet\InvalidStateException;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterSaveController {
  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var Emoji */
  private $emoji;

  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AuthorizedEmailsController $authorizedEmailsController,
    Emoji $emoji,
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    PostNotificationScheduler $postNotificationScheduler,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->emoji = $emoji;
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function save(array $data = []): array {
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
      $template = $this->newsletterTemplatesRepository->findOneById($data['template_id']);
      if ($template) {
        $data['body'] = json_encode($template->getBody());
      }
      unset($data['template_id']);
    }

    $oldNewsletter = null;
    if (isset($data['id'])) {
      $fetched = Newsletter::findOne(intval($data['id']));
      $oldNewsletter = $fetched instanceof Newsletter ? $fetched : null;
    }

    if (!empty($data['body'])) {
      $data['body'] = $this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', $data['body']);
    }
    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();
    if (!empty($errors)) {
      throw UnexpectedValueException::create()->withErrors($errors);
    }

    // Re-fetch newsletter to sync changes made by DB
    // updated_at column use CURRENT_TIMESTAMP for update and this change is not updated automatically by ORM
    $newsletter = Newsletter::findOne($newsletter->id);
    if (!$newsletter) {
      throw new InvalidStateException();
    }

    if (!empty($segments)) {
      NewsletterSegment::where('newsletter_id', $newsletter->id)
        ->deleteMany();
      foreach ($segments as $segment) {
        if (!is_array($segment)) continue;
        $relation = NewsletterSegment::create();
        $relation->segmentId = (int)$segment['id'];
        $relation->newsletterId = $newsletter->id;
        $relation->save();
      }
    }

    // save default sender if needed
    if (!$this->settings->get('sender') && !empty($data['sender_address']) && !empty($data['sender_name'])) {
      $this->settings->set('sender', [
        'address' => $data['sender_address'],
        'name' => $data['sender_name'],
      ]);
    }

    if (!empty($options)) {
      $optionFields = NewsletterOptionField::where(
        'newsletter_type',
        $newsletter->type
      )->findMany();
      // update newsletter options
      foreach ($optionFields as $optionField) {
        if (isset($options[$optionField->name])) {
          $newsletterOption = NewsletterOption::createOrUpdate(
            [
              'newsletter_id' => $newsletter->id,
              'option_field_id' => $optionField->id,
              'value' => $options[$optionField->name],
            ]
          );
        }
      }
      // reload newsletter with updated options
      $newsletter = Newsletter::filter('filterWithOptions', $newsletter->type)->findOne($newsletter->id);
      if (!$newsletter) {
        throw new InvalidStateException();
      }
      // if this is a post notification, process newsletter options and update its schedule
      if ($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        // generate the new schedule from options and get the new "next run" date
        $newsletter->schedule = $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
        $nextRunDate = Scheduler::getNextRunDate($newsletter->schedule);
        // find previously scheduled jobs and reschedule them using the new "next run" date
        SendingQueue::findTaskByNewsletterId($newsletter->id)
          ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $nextRunDate)
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
        $queue->newsletterRenderedBody = null;
        $queue->newsletterRenderedSubject = null;
        $newsletterQueueTask = new NewsletterQueueTask();
        $newsletterQueueTask->preProcessNewsletter($newsletter, $queue);
      }
    }

    $this->wp->doAction('mailpoet_api_newsletters_save_after', $newsletter);
    $this->authorizedEmailsController->onNewsletterUpdate($newsletter, $oldNewsletter);

    $previewUrl = NewsletterUrl::getViewInBrowserUrl($newsletter);
    return [$newsletter->asArray(), ['preview_url' => $previewUrl]];
  }
}
