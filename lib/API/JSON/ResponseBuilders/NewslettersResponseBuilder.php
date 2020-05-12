<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoetVendor\Doctrine\ORM\EntityManager;

use function MailPoetVendor\array_column;

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

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager,
    NewsletterStatisticsRepository $newslettersStatsRepository
  ) {
    $this->newslettersStatsRepository = $newslettersStatsRepository;
    $this->entityManager = $entityManager;
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

  /**
   * @param NewsletterEntity[] $newsletters
   * @return mixed[]
   */
  public function buildForListing(array $newsletters): array {
    $statistics = $this->newslettersStatsRepository->getBatchStatistics($newsletters);
    $latestQueues = $this->getBatchLatestQueuesWithTasks($newsletters);
    $this->prefetchOptions($newsletters);
    $this->prefetchSegments($newsletters);

    $data = [];
    foreach ($newsletters as $newsletter) {
      $id = $newsletter->getId();
      $data[] = $this->buildListingItem($newsletter, $statistics[$id] ?? null, $latestQueues[$id] ?? null);
    }
    return $data;
  }

  private function buildListingItem(NewsletterEntity $newsletter, NewsletterStatistics $statistics = null, SendingQueueEntity $latestQueue = null): array {
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
      'statistics' => ($statistics && $newsletter->getType() !== NewsletterEntity::TYPE_NOTIFICATION)
        ? $statistics->asArray()
        : false,
      'preview_url' => NewsletterUrl::getViewInBrowserUrl(
        (object)[
          'id' => $newsletter->getId(),
          'hash' => $newsletter->getHash(),
        ],
        null,
        in_array($newsletter->getStatus(), [NewsletterEntity::STATUS_SENT, NewsletterEntity::STATUS_SENDING], true)
          ? $latestQueue
          : false
      ),
    ];

    if ($newsletter->getType() === NewsletterEntity::TYPE_STANDARD) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['queue'] = $latestQueue ? $this->buildQueue($latestQueue) : false; // false for BC
    } elseif (in_array($newsletter->getType(), [NewsletterEntity::TYPE_WELCOME, NewsletterEntity::TYPE_AUTOMATIC], true)) {
      $data['segments'] = [];
      $data['options'] = $this->buildOptions($newsletter);
      $data['total_sent'] = $statistics ? $statistics->getTotalSentCount() : 0;
      $data['total_scheduled'] = (int)SendingQueue::findTaskByNewsletterId($newsletter->getId())
        ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
        ->count();
    } elseif ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['children_count'] = $this->newslettersStatsRepository->getChildrenCount($newsletter);
      $data['options'] = $this->buildOptions($newsletter);
    } elseif ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $data['segments'] = $this->buildSegments($newsletter);
      $data['queue'] = $latestQueue ? $this->buildQueue($latestQueue) : false; // false for BC
    }
    return $data;
  }

  private function buildSegments(NewsletterEntity $newsletter) {
    $output = [];
    foreach ($newsletter->getNewsletterSegments() as $newsletterSegment) {
      $segment = $newsletterSegment->getSegment();
      if (!$segment || $segment->getDeletedAt()) {
        continue;
      }
      $output[] = $this->buildSegment($segment);
    }
    return $output;
  }

  private function buildOptions(NewsletterEntity $newsletter) {
    $output = [];
    foreach ($newsletter->getOptions() as $option) {
      $optionField = $option->getOptionField();
      if (!$optionField) {
        continue;
      }
      $output[$optionField->getName()] = $option->getValue();
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
    if ($task === null) {
      return null;
    }
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
      'task_id' => (string)$task->getId(), // (string) for BC
      'newsletter_id' => ($newsletter = $queue->getNewsletter()) ? (string)$newsletter->getId() : null, // (string) for BC
      'newsletter_rendered_subject' => $queue->getNewsletterRenderedSubject(),
      'count_total' => (string)$queue->getCountTotal(), // (string) for BC
      'count_processed' => (string)$queue->getCountProcessed(), // (string) for BC
      'count_to_process' => (string)$queue->getCountToProcess(), // (string) for BC
    ];
  }

  private function getBatchLatestQueuesWithTasks(array $newsletters): array {
    // this implements the same logic as NewsletterEntity::getLatestQueue() but for a batch of $newsletters

    $subqueryQueryBuilder = $this->entityManager->createQueryBuilder();
    $subquery = $subqueryQueryBuilder
      ->select('MAX(subSq.id) AS maxId')
      ->from(SendingQueueEntity::class, 'subSq')
      ->where('subSq.newsletter IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->groupBy('subSq.newsletter')
      ->getQuery();
    $latestQueueIds = array_column($subquery->getResult(), 'maxId');
    if (empty($latestQueueIds)) {
      return [];
    }

    $queryBuilder = $this->entityManager->createQueryBuilder();
    $results = $queryBuilder
      ->select('sq, t, IDENTITY(sq.newsletter)')
      ->from(SendingQueueEntity::class, 'sq')
      ->join('sq.task', 't')
      ->where('sq.id IN (:sub)')
      ->setParameter('sub', $latestQueueIds)
      ->getQuery()
      ->getResult();

    $latestQueues = [];
    foreach ($results as $result) {
      $latestQueues[(int)$result[1]] = $result[0];
    }
    return $latestQueues;
  }

  private function prefetchOptions(array $newsletters) {
    $this->entityManager->createQueryBuilder()
      ->select('PARTIAL n.{id}, o, opf')
      ->from(NewsletterEntity::class, 'n')
      ->join('n.options', 'o')
      ->join('o.optionField', 'opf')
      ->where('n.id IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->getQuery()
      ->getResult();
  }

  private function prefetchSegments(array $newsletters) {
    $this->entityManager->createQueryBuilder()
      ->select('PARTIAL n.{id}, ns, s')
      ->from(NewsletterEntity::class, 'n')
      ->join('n.newsletterSegments', 'ns')
      ->join('ns.segment', 's')
      ->where('n.id IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->getQuery()
      ->getResult();
  }
}
