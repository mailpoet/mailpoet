<?php

namespace MailPoet\Newsletter;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterQueueTask;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\NotFoundException;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Emoji;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewsletterSaveController {
  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var Emoji */
  private $emoji;

  /** @var EntityManager */
  private $entityManager;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    AuthorizedEmailsController $authorizedEmailsController,
    Emoji $emoji,
    EntityManager $entityManager,
    NewslettersRepository $newslettersRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository,
    NewsletterSegmentRepository $newsletterSegmentRepository,
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    PostNotificationScheduler $postNotificationScheduler,
    ScheduledTasksRepository $scheduledTasksRepository,
    SettingsController $settings
  ) {
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->emoji = $emoji;
    $this->entityManager = $entityManager;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->settings = $settings;
  }

  public function save(array $data = []): NewsletterEntity {
    if (!empty($data['template_id'])) {
      $template = $this->newsletterTemplatesRepository->findOneById($data['template_id']);
      if ($template) {
        $data['body'] = json_encode($template->getBody());
      }
    }

    if (!empty($data['body'])) {
      $data['body'] = $this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', $data['body']);
    }

    $newsletter = $this->getNewsletter($data);
    $oldSenderAddress = $newsletter->getSenderAddress();

    $this->updateNewsletter($newsletter, $data);
    if (!empty($data['segments'])) {
      $this->updateSegments($newsletter, $data['segments']);
    }
    if (!empty($data['options'])) {
      $this->updateOptions($newsletter, $data['options']);
    }

    // fetch model with updated options (for back compatibility)
    $newsletterModel = Newsletter::filter('filterWithOptions', $newsletter->getType())->findOne($newsletter->getId());
    if (!$newsletterModel) {
      throw new InvalidStateException();
    }

    // save default sender if needed
    if (!$this->settings->get('sender') && !empty($data['sender_address']) && !empty($data['sender_name'])) {
      $this->settings->set('sender', [
        'address' => $data['sender_address'],
        'name' => $data['sender_name'],
      ]);
    }

    $this->rescheduleIfNeeded($newsletter, $newsletterModel);
    $this->updateQueue($newsletter, $newsletterModel, $data['options'] ?? []);
    $this->authorizedEmailsController->onNewsletterSenderAddressUpdate($newsletter, $oldSenderAddress);
    return $newsletter;
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

  private function updateSegments(NewsletterEntity $newsletter, array $segments) {
    $newsletterSegments = [];
    foreach ($segments as $segmentData) {
      if (!is_array($segmentData) || !isset($segmentData['id'])) {
        continue;
      }

      $segment = $this->entityManager->getReference(SegmentEntity::class, (int)$segmentData['id']);
      $newsletterSegment = $this->newsletterSegmentRepository->findOneBy([
        'newsletter' => $newsletter,
        'segment' => $segment,
      ]);

      if (!$newsletterSegment) {
        $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
        $this->entityManager->persist($newsletterSegment);
      }

      if (!$newsletter->getNewsletterSegments()->contains($newsletterSegment)) {
        $newsletter->getNewsletterSegments()->add($newsletterSegment);
      }
      $newsletterSegments[] = $newsletterSegment;
    }

    // on Doctrine < 2.6, when using orphan removal, we need to remove items manually instead of replacing the
    // whole collection (see https://github.com/doctrine/orm/commit/1587aac4ff6b0753ddd5f8b8d4558b6b40096057)
    foreach ($newsletter->getNewsletterSegments() as $newsletterSegment) {
      if (!in_array($newsletterSegment, $newsletterSegments, true)) {
        $newsletter->getNewsletterSegments()->removeElement($newsletterSegment); // triggers orphan removal
      }
    }

    $this->entityManager->flush();
  }

  private function updateOptions(NewsletterEntity $newsletter, array $options) {
    $optionFields = $this->newsletterOptionFieldsRepository->findBy(['newsletterType' => $newsletter->getType()]);
    foreach ($optionFields as $optionField) {
      if (!isset($options[$optionField->getName()])) {
        continue;
      }

      $option = $this->newsletterOptionsRepository->findOneBy([
        'newsletter' => $newsletter,
        'optionField' => $optionField,
      ]);

      if (!$option) {
        $option = new NewsletterOptionEntity($newsletter, $optionField);
        $this->newsletterOptionsRepository->persist($option);
      }
      $option->setValue($options[$optionField->getName()]);

      if (!$newsletter->getOptions()->contains($option)) {
        $newsletter->getOptions()->add($option);
      }
    }

    $this->entityManager->flush();
  }

  private function rescheduleIfNeeded(NewsletterEntity $newsletter, Newsletter $newsletterModel) {
    if ($newsletter->getType() !== NewsletterEntity::TYPE_NOTIFICATION) {
      return;
    }

    // generate the new schedule from options and get the new "next run" date
    $schedule = $this->postNotificationScheduler->processPostNotificationSchedule($newsletterModel);
    $nextRunDateString = Scheduler::getNextRunDate($schedule);
    $nextRunDate = $nextRunDateString ? Carbon::createFromFormat('Y-m-d H:i:s', $nextRunDateString) : null;

    // find previously scheduled jobs and reschedule them
    $scheduledTasks = $this->scheduledTasksRepository->findByNewsletterAndStatus($newsletter, ScheduledTaskEntity::STATUS_SCHEDULED);
    foreach ($scheduledTasks as $scheduledTask) {
      $scheduledTask->setScheduledAt($nextRunDate);
    }
    $this->entityManager->flush();

    // 'processPostNotificationSchedule' modifies newsletter options by old model - let's reload them
    foreach ($newsletter->getOptions() as $newsletterOption) {
      $this->entityManager->refresh($newsletterOption);
    }
  }

  private function updateQueue(NewsletterEntity $newsletter, Newsletter $newsletterModel, array $options) {
    if ($newsletter->getType() !== NewsletterEntity::TYPE_STANDARD) {
      return;
    }

    $queue = $newsletter->getLatestQueue();
    if (!$queue) {
      return;
    }

    // if newsletter was previously scheduled and is now unscheduled, set its status to DRAFT and delete associated queue record
    if ($newsletter->getStatus() === NewsletterEntity::STATUS_SCHEDULED && isset($options['isScheduled']) && empty($options['isScheduled'])) {
      $this->entityManager->remove($queue);
      $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    } else {
      $queueModel = $newsletterModel->getQueue();
      $queueModel->newsletterRenderedSubject = null;
      $queueModel->newsletterRenderedBody = null;

      $newsletterQueueTask = new NewsletterQueueTask();
      $newsletterQueueTask->preProcessNewsletter($newsletterModel, $queueModel);

      // 'preProcessNewsletter' modifies queue by old model - let's reload it
      $this->entityManager->refresh($queue);
    }
    $this->entityManager->flush();
  }
}
