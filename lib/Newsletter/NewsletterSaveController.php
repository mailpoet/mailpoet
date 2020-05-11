<?php

namespace MailPoet\Newsletter;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterQueueTask;
use MailPoet\Entities\NewsletterEntity;
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
use MailPoet\NotFoundException;
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

  /** @var NewslettersRepository */
  private $newslettersRepository;

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
    NewslettersRepository $newslettersRepository,
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    PostNotificationScheduler $postNotificationScheduler,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->emoji = $emoji;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function save(array $data = []): array {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);

    if (!empty($data['template_id'])) {
      $template = $this->newsletterTemplatesRepository->findOneById($data['template_id']);
      if ($template) {
        $data['body'] = json_encode($template->getBody());
      }
    }

    $oldNewsletterModel = null;
    if (isset($data['id'])) {
      $fetched = Newsletter::findOne(intval($data['id']));
      $oldNewsletterModel = $fetched instanceof Newsletter ? $fetched : null;
    }

    if (!empty($data['body'])) {
      $data['body'] = $this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', $data['body']);
    }

    $newsletter = $this->getNewsletter($data);
    $this->updateNewsletter($newsletter, $data);

    // fetch old model for back compatibility
    $newsletterModel = Newsletter::findOne((int)$data['id']);
    if (!$newsletterModel) {
      throw new InvalidStateException();
    }

    $segments = $data['segments'] ?? [];
    if ($segments) {
      NewsletterSegment::where('newsletter_id', $newsletterModel->id)
        ->deleteMany();
      foreach ($segments as $segment) {
        if (!is_array($segment)) continue;
        $relation = NewsletterSegment::create();
        $relation->segmentId = (int)$segment['id'];
        $relation->newsletterId = $newsletterModel->id;
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

    $options = $data['options'] ?? [];
    if ($options) {
      $optionFields = NewsletterOptionField::where(
        'newsletter_type',
        $newsletterModel->type
      )->findMany();
      // update newsletter options
      foreach ($optionFields as $optionField) {
        if (isset($options[$optionField->name])) {
          $newsletterOption = NewsletterOption::createOrUpdate(
            [
              'newsletter_id' => $newsletterModel->id,
              'option_field_id' => $optionField->id,
              'value' => $options[$optionField->name],
            ]
          );
        }
      }
      // reload newsletter with updated options
      $newsletterModel = Newsletter::filter('filterWithOptions', $newsletterModel->type)->findOne($newsletterModel->id);
      if (!$newsletterModel) {
        throw new InvalidStateException();
      }
      // if this is a post notification, process newsletter options and update its schedule
      if ($newsletterModel->type === Newsletter::TYPE_NOTIFICATION) {
        // generate the new schedule from options and get the new "next run" date
        $newsletterModel->schedule = $this->postNotificationScheduler->processPostNotificationSchedule($newsletterModel);
        $nextRunDate = Scheduler::getNextRunDate($newsletterModel->schedule);
        // find previously scheduled jobs and reschedule them using the new "next run" date
        SendingQueue::findTaskByNewsletterId($newsletterModel->id)
          ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $nextRunDate)
          ->save();
      }
    }

    $queue = $newsletterModel->getQueue();
    if ($queue && !in_array($newsletterModel->type, [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_NOTIFICATION_HISTORY])) {
      // if newsletter was previously scheduled and is now unscheduled, set its status to DRAFT and delete associated queue record
      if ($newsletterModel->status === Newsletter::STATUS_SCHEDULED && isset($options['isScheduled']) && empty($options['isScheduled'])) {
        $queue->delete();
        $newsletterModel->status = Newsletter::STATUS_DRAFT;
        $newsletterModel->save();
      } else {
        $queue->newsletterRenderedBody = null;
        $queue->newsletterRenderedSubject = null;
        $newsletterQueueTask = new NewsletterQueueTask();
        $newsletterQueueTask->preProcessNewsletter($newsletterModel, $queue);
      }
    }

    $this->wp->doAction('mailpoet_api_newsletters_save_after', $newsletterModel);
    $this->authorizedEmailsController->onNewsletterUpdate($newsletterModel, $oldNewsletterModel);

    $previewUrl = NewsletterUrl::getViewInBrowserUrl($newsletterModel);
    return [$newsletterModel->asArray(), ['preview_url' => $previewUrl]];
  }

  private function getNewsletter(array $data): NewsletterEntity {
    if (!isset($data['id'])) {
      throw new UnexpectedValueException();
    }

    $newsletter = $this->newslettersRepository->findOneById((int)$data['id']);
    if (!$newsletter) {
      throw new NotFoundException();
    }
    return $newsletter;
  }

  private function updateNewsletter(NewsletterEntity $newsletter, array $data) {
    if (array_key_exists('type', $data)) {
      $newsletter->setType($data['type']);
    }

    if (array_key_exists('subject', $data)) {
      $newsletter->setSubject($data['subject']);
    }

    if (array_key_exists('preheader', $data)) {
      $newsletter->setPreheader($data['preheader']);
    }

    if (array_key_exists('body', $data)) {
      $newsletter->setBody(json_decode($data['body'], true));
    }

    if (array_key_exists('ga_campaign', $data)) {
      $newsletter->setGaCampaign($data['ga_campaign']);
    }

    if (array_key_exists('sender_name', $data)) {
      $newsletter->setSenderName($data['sender_name'] ?? '');
    }

    if (array_key_exists('sender_address', $data)) {
      $newsletter->setSenderAddress($data['sender_address'] ?? '');
    }

    if (array_key_exists('reply_to_name', $data)) {
      $newsletter->setReplyToName($data['reply_to_name'] ?? '');
    }

    if (array_key_exists('reply_to_address', $data)) {
      $newsletter->setReplyToAddress($data['reply_to_address'] ?? '');
    }

    $this->newslettersRepository->flush();
  }
}
