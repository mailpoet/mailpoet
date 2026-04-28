<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\WP\Functions as WPFunctions;

class UnsubscribeReasonTracker {
  const MAX_REASON_TEXT_LENGTH = 500;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SendingQueuesRepository $sendingQueuesRepository,
    StatisticsUnsubscribesRepository $statisticsUnsubscribesRepository,
    WPFunctions $wp
  ) {
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->statisticsUnsubscribesRepository = $statisticsUnsubscribesRepository;
    $this->wp = $wp;
  }

  /**
   * @return array<string, string>
   */
  public function getReasonLabels(): array {
    return [
      StatisticsUnsubscribeEntity::REASON_TOO_MANY_EMAILS => __('Too many emails', 'mailpoet'),
      StatisticsUnsubscribeEntity::REASON_NOT_RELEVANT => __('Content is not relevant', 'mailpoet'),
      StatisticsUnsubscribeEntity::REASON_DO_NOT_REMEMBER_SIGNING_UP => __('I do not remember signing up', 'mailpoet'),
      StatisticsUnsubscribeEntity::REASON_NO_LONGER_INTERESTED => __('I am no longer interested', 'mailpoet'),
      StatisticsUnsubscribeEntity::REASON_TOO_PROMOTIONAL => __('Emails are too promotional', 'mailpoet'),
      StatisticsUnsubscribeEntity::REASON_OTHER => __('Other', 'mailpoet'),
    ];
  }

  public function isValidReason(string $reason): bool {
    return in_array($reason, StatisticsUnsubscribeEntity::REASONS, true);
  }

  public function findTargetUnsubscribe(SubscriberEntity $subscriber, ?int $queueId): ?StatisticsUnsubscribeEntity {
    if ($queueId !== null) {
      $queue = $this->sendingQueuesRepository->findOneById($queueId);
      if (!$queue instanceof SendingQueueEntity) {
        return null;
      }
      $newsletter = $queue->getNewsletter();
      if (!$newsletter instanceof NewsletterEntity) {
        return null;
      }
      return $this->statisticsUnsubscribesRepository->findOneBySubscriberAndQueue($subscriber, $queue, $newsletter);
    }

    return $this->statisticsUnsubscribesRepository->findLatestForSubscriber($subscriber);
  }

  public function saveReason(
    SubscriberEntity $subscriber,
    ?int $queueId,
    string $reason,
    ?string $reasonText,
    bool $allowOtherText
  ): ?StatisticsUnsubscribeEntity {
    if (!$this->isValidReason($reason)) {
      return null;
    }

    $unsubscribe = $this->findTargetUnsubscribe($subscriber, $queueId);
    if (!$unsubscribe instanceof StatisticsUnsubscribeEntity) {
      return null;
    }

    $unsubscribe->setReasonData($reason, $this->prepareReasonText($reason, $reasonText, $allowOtherText));
    $this->statisticsUnsubscribesRepository->persist($unsubscribe);
    $this->statisticsUnsubscribesRepository->flush();

    return $unsubscribe;
  }

  private function prepareReasonText(string $reason, ?string $reasonText, bool $allowOtherText): ?string {
    if (!$allowOtherText || $reason !== StatisticsUnsubscribeEntity::REASON_OTHER || $reasonText === null) {
      return null;
    }

    $reasonText = $this->wp->wpStripAllTags($reasonText, true);
    $reasonText = trim($reasonText);
    if ($reasonText === '') {
      return null;
    }

    return function_exists('mb_substr')
      ? mb_substr($reasonText, 0, self::MAX_REASON_TEXT_LENGTH)
      : substr($reasonText, 0, self::MAX_REASON_TEXT_LENGTH);
  }
}
