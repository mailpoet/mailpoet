<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;

class NewslettersResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  const RELATION_QUEUE = 'queue';
  const RELATION_SEGMENTS = 'segments';
  const RELATION_OPTIONS = 'options';
  const RELATION_TOTAL_SENT = 'total_sent';
  const RELATION_CHILDREN_COUNT = 'children_count';
  const RELATION_SCHEDULED = 'scheduled';
  const RELATION_STATISTICS = 'statistics';

  /** @var NewsletterStatisticsRepository */
  private $newslettersStatsRepository;

  public function __construct(NewsletterStatisticsRepository $newslettersStatsRepository) {
    $this->newslettersStatsRepository = $newslettersStatsRepository;
  }

  public function build(NewsletterEntity $newsletter, $relations = []) {
    $data = [
      'id' => (string)$newsletter->getId(), // (string) for BC
      'hash' => $newsletter->getHash(),
      'subject' => $newsletter->getSubject(),
      'type' => $newsletter->getType(),
      'sender_address' => $newsletter->getSenderAddress(),
      'sender_name' => $newsletter->getSenderName(),
      'status' => $newsletter->getStatus(),
      'reply_to_address' => $newsletter->getReplyToAddress(),
      'reply_to_name' => $newsletter->getReplyToName(),
      'preheader' => $newsletter->getPreheader(),
      'body' => $newsletter->getBody(),
      'sent_at' => ($sentAt = $newsletter->getSentAt()) ? $sentAt->format(self::DATE_FORMAT) : null,
      'created_at' => $newsletter->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $newsletter->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $newsletter->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'parent_id' => ($parent = $newsletter->getParent()) ? $parent->getId() : null,
      'unsubscribe_token' => $newsletter->getUnsubscribeToken(),
      'ga_campaign' => $newsletter->getGaCampaign(),
    ];

    foreach ($relations as $relation) {
      if ($relation === self::RELATION_QUEUE) {
        $data['queue'] = ($queue = $newsletter->getLatestQueue()) ? $this->buildQueue($queue) : false; // false for BC
      }
      if ($relation === self::RELATION_SEGMENTS) {
        $data['segments'] = $this->buildSegments($newsletter);
      }
      if ($relation === self::RELATION_OPTIONS) {
        $data['options'] = $this->buildOptions($newsletter);
      }
      if ($relation === self::RELATION_TOTAL_SENT) {
        $data['total_sent'] = $this->newslettersStatsRepository->getTotalSentCount($newsletter);
      }
      if ($relation === self::RELATION_CHILDREN_COUNT) {
        $data['children_count'] = $this->newslettersStatsRepository->getChildrenCount($newsletter);
      }
      if ($relation === self::RELATION_SCHEDULED) {
        $data['total_scheduled'] = (int)SendingQueue::findTaskByNewsletterId($newsletter->getId())
          ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
          ->count();
      }
      if ($relation === self::RELATION_STATISTICS) {
        $data['statistics'] = $this->newslettersStatsRepository->getStatistics($newsletter)->asArray();
      }
    }
    return $data;
  }

  public function buildForListing(NewsletterEntity $newsletter): array {
    $data = [
      'id' => (string)$newsletter->getId(), // (string) for BC
      'hash' => $newsletter->getHash(),
      'subject' => $newsletter->getSubject(),
      'type' => $newsletter->getType(),
      'status' => $newsletter->getStatus(),
      'sent_at' => ($sentAt = $newsletter->getSentAt()) ? $sentAt->format(self::DATE_FORMAT) : null,
      'updated_at' => $newsletter->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $newsletter->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'segments' => [],
      'queue' => false,
      'statistics' => false,
    ];

    if ($newsletter->getType() === NewsletterEntity::TYPE_STANDARD) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['statistics'] = $this->newslettersStatsRepository->getStatistics($newsletter)->asArray();
      $data['queue'] = ($queue = $newsletter->getLatestQueue()) ? $this->buildQueue($queue) : false; // false for BC
    } elseif (in_array($newsletter->getType(), [NewsletterEntity::TYPE_WELCOME, NewsletterEntity::TYPE_AUTOMATIC], true)) {
      $data['segments'] = [];
      $data['statistics'] = $this->newslettersStatsRepository->getStatistics($newsletter)->asArray();
      $data['options'] = $this->buildOptions($newsletter);
      $data['total_sent'] = $this->newslettersStatsRepository->getTotalSentCount($newsletter);
      $data['total_scheduled'] = (int)SendingQueue::findTaskByNewsletterId($newsletter->getId())
        ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
        ->count();
    } elseif ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['children_count'] = $this->newslettersStatsRepository->getChildrenCount($newsletter);
      $data['options'] = $this->buildOptions($newsletter);
    } elseif ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['statistics'] = $this->newslettersStatsRepository->getStatistics($newsletter)->asArray();
      $data['queue'] = ($queue = $newsletter->getLatestQueue()) ? $this->buildQueue($queue) : false; // false for BC
    }
    return $data;
  }

  private function buildSegments(NewsletterEntity $newsletter) {
    $output = [];
    foreach ($newsletter->getNewsletterSegments() as $newsletterSegment) {
      $segment = $newsletterSegment->getSegment();
      if ($segment->getDeletedAt()) {
        continue;
      }
      $output[] = $this->buildSegment($segment);
    }
    return $output;
  }

  private function buildOptions(NewsletterEntity $newsletter) {
    $output = [];
    foreach ($newsletter->getOptions() as $option) {
      $output[$option->getOptionField()->getName()] = $option->getValue();
    }

    // convert 'afterTimeNumber' string to integer
    if (isset($output['afterTimeNumber']) && is_numeric($output['afterTimeNumber'])) {
      $output['afterTimeNumber'] = (int)$output['afterTimeNumber'];
    }

    return $output;
  }

  private function buildSegment(SegmentEntity $segment) {
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => $segment->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }

  private function buildQueue(SendingQueueEntity $queue) {
    $task = $queue->getTask();

    // the following crazy mix of '$queue' and '$task' comes from 'array_merge($task, $queue)'
    // (MailPoet\Tasks\Sending) which means all equal-named fields will be taken from '$queue'
    return [
      'id' => (string)$queue->getId(), // (string) for BC
      'type' => $task->getType(),
      'status' => $task->getStatus(),
      'priority' => (string)$task->getPriority(), // (string) for BC
      'scheduled_at' => ($scheduledAt = $task->getScheduledAt()) ? $scheduledAt->format(self::DATE_FORMAT) : null,
      'processed_at' => ($processedAt = $task->getProcessedAt()) ? $processedAt->format(self::DATE_FORMAT) : null,
      'created_at' => $queue->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $queue->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $queue->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'meta' => $queue->getMeta(),
      'task_id' => (string)$queue->getTask()->getId(), // (string) for BC
      'newsletter_id' => (string)$queue->getNewsletter()->getId(), // (string) for BC
      'newsletter_rendered_subject' => $queue->getNewsletterRenderedSubject(),
      'count_total' => (string)$queue->getCountTotal(), // (string) for BC
      'count_processed' => (string)$queue->getCountProcessed(), // (string) for BC
      'count_to_process' => (string)$queue->getCountToProcess(), // (string) for BC
    ];
  }
}
