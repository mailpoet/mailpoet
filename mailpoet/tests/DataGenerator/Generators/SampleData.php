<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator\Generators;

use MailPoet\Automation\Engine\Data\Automation as AutomationData;
use MailPoet\Automation\Engine\Data\AutomationRun as AutomationRunData;
use MailPoet\Automation\Engine\Data\AutomationRunLog as AutomationRunLogData;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\AutomationRun as AutomationRunFactory;
use MailPoet\Test\DataFactories\AutomationRunLog as AutomationRunLogFactory;
use MailPoet\Test\DataFactories\DynamicSegment as DynamicSegmentFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * Generates a customizable MailPoet sample dataset for local development and tests.
 *
 * Local usage examples:
 *   pnpm generate:sample-data
 *   pnpm generate:sample-data --preset=small --subscribers=100 --woocommerce=0
 *   pnpm generate:sample-data 4 --preset=large --open-rate=0.5 --purchase-rate=0.2
 */
class SampleData implements Generator {
  private const BULK_INSERT_BATCH_SIZE = 500;

  private const STATUS_DISTRIBUTION = [
    SubscriberEntity::STATUS_SUBSCRIBED => 70,
    SubscriberEntity::STATUS_UNSUBSCRIBED => 10,
    SubscriberEntity::STATUS_INACTIVE => 8,
    SubscriberEntity::STATUS_UNCONFIRMED => 7,
    SubscriberEntity::STATUS_BOUNCED => 5,
  ];

  private const SUBSCRIBER_TIME_ZONE_SHARE = 70;

  private const SUBSCRIBER_TIME_ZONES = [
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'America/Sao_Paulo',
    'Europe/London',
    'Europe/Berlin',
    'Europe/Prague',
    'Asia/Kolkata',
    'Asia/Tokyo',
    'Australia/Sydney',
  ];

  private const AUTOMATION_RUN_STATUS_DISTRIBUTION = [
    AutomationRunData::STATUS_COMPLETE => 78,
    AutomationRunData::STATUS_RUNNING => 8,
    AutomationRunData::STATUS_FAILED => 8,
    AutomationRunData::STATUS_CANCELLED => 6,
  ];

  /** @var SampleDataConfig */
  private $config;

  /** @var EntityManager */
  private $entityManager;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function __construct(
    ?SampleDataConfig $config = null
  ) {
    $this->config = $config ?? SampleDataConfig::fromArray();
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
  }

  public function generate() {
    remove_all_actions('woocommerce_order_status_changed');
    remove_all_actions('woocommerce_order_status_completed');
    remove_all_actions('woocommerce_order_status_processing');

    $runSuffix = $this->getRandomString();

    $lists = $this->createLists($runSuffix);
    yield sprintf('Lists done (%d)', count($lists));

    $subscribersByList = array_fill_keys(array_keys($lists), []);
    $allSubscribers = $this->createSubscribers($lists, $subscribersByList, $runSuffix);
    yield sprintf('Subscribers done (%d)', count($allSubscribers));

    $wooCommerceData = $this->createWooCommerceData($runSuffix);
    if ($wooCommerceData === null) {
      yield 'WooCommerce data skipped';
    } else {
      yield sprintf('WooCommerce products done (%d)', count($wooCommerceData['products']));
    }

    $dynamicSegments = $this->createDynamicSegments($runSuffix, $wooCommerceData);
    yield sprintf('Dynamic segments done (%d)', count($dynamicSegments));

    $this->createDraftNewsletters($lists, $runSuffix);
    yield sprintf('Draft newsletters done (%d)', $this->config->getDraftNewslettersCount());

    $sentEmails = [];
    foreach ($this->createSentStandardNewsletters($lists, $subscribersByList, $runSuffix) as $messageAndEmail) {
      $sentEmails[] = $messageAndEmail['email'];
      yield $messageAndEmail['message'];
    }
    yield sprintf('Sent standard newsletters done (%d)', $this->config->getSentNewslettersCount());

    $postNotificationEmails = $this->createPostNotificationHistory($lists, $subscribersByList, $runSuffix);
    $sentEmails = array_merge($sentEmails, $postNotificationEmails);
    yield sprintf('Post notification history emails done (%d)', count($postNotificationEmails));

    $welcomeEmails = $this->createWelcomeEmails($lists, $subscribersByList, $runSuffix);
    $sentEmails = array_merge($sentEmails, $welcomeEmails);
    yield sprintf('Welcome emails done (%d)', count($welcomeEmails));

    $automaticEmails = $this->createLegacyAutomaticEmails($allSubscribers, $wooCommerceData, $runSuffix);
    $sentEmails = array_merge($sentEmails, $automaticEmails);
    yield sprintf('Legacy automatic emails done (%d)', count($automaticEmails));

    $clicksForRevenue = $this->generateOpensAndClicks($sentEmails, $allSubscribers);
    yield sprintf('Opens and clicks done (candidate clicks for revenue: %d)', count($clicksForRevenue));

    if ($wooCommerceData !== null && $clicksForRevenue) {
      $orderCount = $this->generateRevenue($clicksForRevenue, $allSubscribers, $wooCommerceData['products']);
      yield sprintf('Revenue done (%d orders)', $orderCount);
    }

    $automations = $this->createAutomations($runSuffix);
    yield sprintf('Automations done (%d)', count($automations));

    $automationRuns = $this->createAutomationRuns($automations, $allSubscribers);
    yield sprintf('Automation runs done (%d)', $automationRuns);

    $this->entityManager->flush();
  }

  public function runBefore() {
    $connection = $this->entityManager->getConnection();
    $connection->executeStatement(
      'ALTER TABLE `' . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . '`
      CHANGE `updated_at` `updated_at` timestamp NULL;'
    );
  }

  public function runAfter() {
    $connection = $this->entityManager->getConnection();
    $connection->executeStatement(
      'ALTER TABLE `' . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . '`
      CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;'
    );
  }

  /**
   * @return array<int, SegmentEntity>
   */
  private function createLists(string $runSuffix): array {
    $factory = new SegmentFactory();
    $lists = [];
    for ($i = 1; $i <= $this->config->getListsCount(); $i++) {
      $segment = $factory
        ->withName(sprintf('%s list %d (%s)', $this->config->getPrefix(), $i, $runSuffix))
        ->withDescription('Created by the sample data generator')
        ->create();
      $lists[$segment->getId()] = $segment;
    }
    return $lists;
  }

  /**
   * @param array<int, SegmentEntity> $lists
   * @param array<int, int[]> $subscribersByList
   * @return array<int, SubscriberEntity>
   */
  private function createSubscribers(array $lists, array &$subscribersByList, string $runSuffix): array {
    $allSubscribers = [];
    for ($i = 1; $i <= $this->config->getSubscribersCount(); $i++) {
      $status = $this->pickStatusByWeight();
      $createdAt = $this->randomPastDate();
      $subscriberLists = $this->pickRandomElements($lists, random_int(1, min(3, count($lists))));

      $subscriberFactory = (new SubscriberFactory())
        ->withEmail(sprintf('sample-%s-%05d@%s', $runSuffix, $i, $this->config->getEmailDomain()))
        ->withStatus($status)
        ->withFirstName('Sample')
        ->withLastName('Subscriber ' . $i)
        ->withSource('imported')
        ->withEngagementScore(random_int(0, 100))
        ->withCreatedAt($createdAt);

      $timeZone = random_int(1, 100) <= self::SUBSCRIBER_TIME_ZONE_SHARE
        ? self::SUBSCRIBER_TIME_ZONES[array_rand(self::SUBSCRIBER_TIME_ZONES)]
        : null;
      if ($timeZone !== null) {
        $subscriberFactory->withTimeZone($timeZone);
      }

      $subscriber = $subscriberFactory->create();
      if ($timeZone !== null) {
        $subscriber->setTimeZoneSource(SubscriberEntity::TIME_ZONE_SOURCE_FORM);
        $subscriber->setTimeZoneConfidence(SubscriberEntity::TIME_ZONE_CONFIDENCE_BROWSER);
        $subscriber->setTimeZoneUpdatedAt($createdAt);
      }

      $segmentStatus = $status === SubscriberEntity::STATUS_UNSUBSCRIBED
        ? SubscriberEntity::STATUS_UNSUBSCRIBED
        : SubscriberEntity::STATUS_SUBSCRIBED;
      foreach ($subscriberLists as $list) {
        $subscriberSegment = new SubscriberSegmentEntity($list, $subscriber, $segmentStatus);
        $this->entityManager->persist($subscriberSegment);
        if ($status === SubscriberEntity::STATUS_SUBSCRIBED) {
          $subscribersByList[$list->getId()][] = $subscriber->getId();
        }
      }

      $allSubscribers[$subscriber->getId()] = $subscriber;

      if ($i % self::BULK_INSERT_BATCH_SIZE === 0) {
        $this->entityManager->flush();
        $this->entityManager->clear(SubscriberSegmentEntity::class);
      }
    }
    $this->entityManager->flush();
    return $allSubscribers;
  }

  /**
   * @return array{products: array<int, \WC_Product>, category: ?\WP_Term}|null
   */
  private function createWooCommerceData(string $runSuffix): ?array {
    if (!$this->config->shouldGenerateWooCommerceData() || $this->config->getProductsCount() === 0) {
      return null;
    }
    if (!class_exists(\WC_Product::class) || !function_exists('wc_create_order')) {
      return null;
    }

    $category = $this->createProductCategory(
      sprintf('%s products (%s)', $this->config->getPrefix(), $runSuffix),
      sprintf('sample-products-%s', $runSuffix)
    );

    $products = [];
    for ($i = 1; $i <= $this->config->getProductsCount(); $i++) {
      $product = new \WC_Product();
      $price = (string)random_int(10, 200);
      $product->set_name(sprintf('%s product %d (%s)', $this->config->getPrefix(), $i, $runSuffix));
      $product->set_status('publish');
      $product->set_price($price);
      $product->set_regular_price($price);
      if ($category instanceof \WP_Term) {
        $product->set_category_ids([$category->term_id]); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      }
      $product->save();
      $products[] = $product;
    }
    return [
      'products' => $products,
      'category' => $category instanceof \WP_Term ? $category : null,
    ];
  }

  /**
   * @param array{products: array<int, \WC_Product>, category: ?\WP_Term}|null $wooCommerceData
   * @return SegmentEntity[]
   */
  private function createDynamicSegments(string $runSuffix, ?array $wooCommerceData): array {
    $segments = [];
    for ($i = 1; $i <= $this->config->getDynamicSegmentsCount(); $i++) {
      $factory = (new DynamicSegmentFactory())
        ->withName(sprintf('%s dynamic segment %d (%s)', $this->config->getPrefix(), $i, $runSuffix));

      if ($wooCommerceData !== null && $i === 2 && $wooCommerceData['products']) {
        $factory = $factory->withWooCommerceProductFilter($wooCommerceData['products'][0]->get_id());
      } elseif ($wooCommerceData !== null && $i === 3 && $wooCommerceData['category'] instanceof \WP_Term) {
        $factory = $factory->withWooCommerceCategoryFilter($wooCommerceData['category']->term_id); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      } else {
        $factory = $factory->withEngagementScoreFilter(50, 'higherThan');
      }
      $segments[] = $factory->create();
    }
    return $segments;
  }

  /**
   * @param array<int, SegmentEntity> $lists
   */
  private function createDraftNewsletters(array $lists, string $runSuffix): void {
    if (!$lists) {
      return;
    }
    for ($i = 1; $i <= $this->config->getDraftNewslettersCount(); $i++) {
      $targetLists = $this->pickRandomElements($lists, random_int(1, min(2, count($lists))));
      (new NewsletterFactory())
        ->withSubject(sprintf('[%s draft %d %s] Newsletter draft', $this->config->getPrefix(), $i, $runSuffix))
        ->withDraftStatus()
        ->withSegments($targetLists)
        ->withCreatedAt($this->randomPastDate()->toDateTimeString())
        ->create();
    }
  }

  /**
   * @param array<int, SegmentEntity> $lists
   * @param array<int, int[]> $subscribersByList
   * @return \Generator<array{message: string, email: array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}}>
   */
  private function createSentStandardNewsletters(array $lists, array $subscribersByList, string $runSuffix): \Generator {
    if (!$lists) {
      return;
    }

    $listIds = array_keys($lists);
    shuffle($listIds);
    for ($i = 1; $i <= $this->config->getSentNewslettersCount(); $i++) {
      $primaryId = $listIds[($i - 1) % count($listIds)];
      $targetListIds = [$primaryId];
      if (count($listIds) > 1 && random_int(0, 1) === 1) {
        $extraCandidates = array_values(array_diff($listIds, [$primaryId]));
        $targetListIds[] = $extraCandidates[array_rand($extraCandidates)];
      }

      $targetLists = array_map(function($id) use ($lists) {
        return $lists[$id];
      }, $targetListIds);
      $recipientIds = $this->getRecipientsForLists($targetListIds, $subscribersByList);
      $sentAt = $this->randomPastDate()->toDateTimeString();

      $newsletter = (new NewsletterFactory())
        ->withSubject(sprintf('[%s sent %d %s] Newsletter', $this->config->getPrefix(), $i, $runSuffix))
        ->withSegments($targetLists)
        ->withCreatedAt($sentAt)
        ->create();

      yield [
        'message' => sprintf('Sent newsletter %d/%d (to %d recipients)', $i, $this->config->getSentNewslettersCount(), count($recipientIds)),
        'email' => $this->createSentEmailData($newsletter, $sentAt, $recipientIds),
      ];
    }
  }

  /**
   * @param array<int, SegmentEntity> $lists
   * @param array<int, int[]> $subscribersByList
   * @return array<int, array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}>
   */
  private function createPostNotificationHistory(array $lists, array $subscribersByList, string $runSuffix): array {
    if (!$lists || $this->config->getPostNotificationsCount() === 0) {
      return [];
    }

    $targetLists = array_values($lists);
    $recipientIds = $this->getRecipientsForLists(array_keys($lists), $subscribersByList);
    $createdAt = $this->randomPastDate()->toDateTimeString();
    $parent = (new NewsletterFactory())
      ->withSubject(sprintf('[%s %s] Post notifications', $this->config->getPrefix(), $runSuffix))
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withSegments($targetLists)
      ->withCreatedAt($createdAt)
      ->create();

    $sentEmails = [];
    for ($i = 1; $i <= $this->config->getPostNotificationsCount(); $i++) {
      $sentAt = $this->randomPastDate()->toDateTimeString();
      $newsletter = (new NewsletterFactory())
        ->withSubject(sprintf('[%s post %d %s] Post notification', $this->config->getPrefix(), $i, $runSuffix))
        ->withPostNotificationHistoryType()
        ->withSegments($targetLists)
        ->withCreatedAt($sentAt)
        ->withParent($parent)
        ->create();
      $sentEmails[] = $this->createSentEmailData($newsletter, $sentAt, $recipientIds);
    }
    return $sentEmails;
  }

  /**
   * @param array<int, SegmentEntity> $lists
   * @param array<int, int[]> $subscribersByList
   * @return array<int, array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}>
   */
  private function createWelcomeEmails(array $lists, array $subscribersByList, string $runSuffix): array {
    if (!$this->config->shouldGenerateWelcomeEmails() || !$lists) {
      return [];
    }

    $list = reset($lists);
    if (!$list instanceof SegmentEntity) {
      return [];
    }
    $recipientIds = $subscribersByList[$list->getId()] ?? [];
    $sentAt = $this->randomPastDate()->toDateTimeString();
    $newsletter = (new NewsletterFactory())
      ->withSubject(sprintf('[%s welcome %s] Welcome email', $this->config->getPrefix(), $runSuffix))
      ->withActiveStatus()
      ->withWelcomeTypeForSegment($list->getId())
      ->withSegments([$list])
      ->withCreatedAt($sentAt)
      ->create();

    return [$this->createSentEmailData($newsletter, $sentAt, $recipientIds)];
  }

  /**
   * @param array<int, SubscriberEntity> $allSubscribers
   * @param array{products: array<int, \WC_Product>, category: ?\WP_Term}|null $wooCommerceData
   * @return array<int, array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}>
   */
  private function createLegacyAutomaticEmails(array $allSubscribers, ?array $wooCommerceData, string $runSuffix): array {
    if ($wooCommerceData === null || $this->config->getAutomaticEmailsCount() === 0 || !$allSubscribers) {
      return [];
    }

    $products = $wooCommerceData['products'];
    if (!$products) {
      return [];
    }

    $subscriberIds = array_keys(array_filter($allSubscribers, function(SubscriberEntity $subscriber): bool {
      return $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED;
    }));
    if (!$subscriberIds) {
      return [];
    }

    $sentEmails = [];
    for ($i = 1; $i <= $this->config->getAutomaticEmailsCount(); $i++) {
      $newsletterFactory = (new NewsletterFactory())
        ->withSubject(sprintf('[%s automatic %d %s] Automatic email', $this->config->getPrefix(), $i, $runSuffix))
        ->withActiveStatus()
        ->withSegments([])
        ->withCreatedAt($this->randomPastDate()->toDateTimeString());

      if ($i === 1) {
        $newsletterFactory = $newsletterFactory->withAutomaticTypeWooCommerceFirstPurchase();
      } elseif ($i % 2 === 0 && $wooCommerceData['category'] instanceof \WP_Term) {
        $product = $products[array_rand($products)];
        $newsletterFactory = $newsletterFactory->withAutomaticTypeWooCommerceProductInCategoryPurchased([[
          'id' => $product->get_id(),
          'name' => $product->get_name(),
          'categories' => [[
            'id' => $wooCommerceData['category']->term_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            'name' => $wooCommerceData['category']->name,
          ]],
        ]]);
      } else {
        $product = $products[array_rand($products)];
        $newsletterFactory = $newsletterFactory->withAutomaticTypeWooCommerceProductPurchased([[
          'id' => $product->get_id(),
          'name' => $product->get_name(),
        ]]);
      }

      $recipientCount = max(1, (int)floor(count($subscriberIds) * 0.25));
      $recipients = $this->pickRandomValues($subscriberIds, $recipientCount);
      $sentAt = $this->randomPastDate()->toDateTimeString();
      $sentEmails[] = $this->createSentEmailData($newsletterFactory->create(), $sentAt, $recipients);
    }
    return $sentEmails;
  }

  /**
   * @param int[] $recipientIds
   * @return array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}
   */
  private function createSentEmailData(NewsletterEntity $newsletter, string $sentAt, array $recipientIds): array {
    $connection = $this->entityManager->getConnection();

    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setCreatedAt(new Carbon($sentAt));
    $task->setProcessedAt(new Carbon($sentAt));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $queue->setCountTotal(count($recipientIds));
    $queue->setCountProcessed(count($recipientIds));
    $queue->setNewsletterRenderedSubject($newsletter->getSubject());
    $this->sendingQueuesRepository->persist($queue);
    $this->sendingQueuesRepository->flush();

    $this->entityManager->refresh($newsletter);

    $taskSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $statsNewslettersTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $taskId = $task->getId();
    $queueId = $queue->getId();
    $newsletterId = $newsletter->getId();

    $taskBatch = [];
    $statsBatch = [];
    foreach ($recipientIds as $subscriberId) {
      $taskBatch[] = "($taskId, $subscriberId, 1, '$sentAt')";
      $statsBatch[] = "($newsletterId, $subscriberId, $queueId, '$sentAt')";
      if (count($taskBatch) >= self::BULK_INSERT_BATCH_SIZE) {
        $connection->executeStatement(
          "INSERT INTO $taskSubscribersTable (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $taskBatch)
        );
        $connection->executeStatement(
          "INSERT INTO $statsNewslettersTable (`newsletter_id`, `subscriber_id`, `queue_id`, `sent_at`) VALUES " . implode(', ', $statsBatch)
        );
        $taskBatch = [];
        $statsBatch = [];
      }
    }
    if ($taskBatch) {
      $connection->executeStatement(
        "INSERT INTO $taskSubscribersTable (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $taskBatch)
      );
      $connection->executeStatement(
        "INSERT INTO $statsNewslettersTable (`newsletter_id`, `subscriber_id`, `queue_id`, `sent_at`) VALUES " . implode(', ', $statsBatch)
      );
    }

    $link = (new NewsletterLinkFactory($newsletter))->withCreatedAt($sentAt)->create();

    if ($newsletter->getStatus() === NewsletterEntity::STATUS_DRAFT) {
      $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
      $newsletter->setSentAt(Carbon::createFromFormat('Y-m-d H:i:s', $sentAt));
      $this->entityManager->flush();
    }

    return [
      'newsletter_id' => $newsletterId,
      'queue_id' => $queueId,
      'sent_at' => strtotime($sentAt),
      'link_id' => $link->getId(),
    ];
  }

  /**
   * @param array<int, array{newsletter_id: int, queue_id: int, sent_at: int, link_id: int}> $sentEmails
   * @param array<int, SubscriberEntity> $allSubscribers
   * @return array<int, array{subscriber_id: int, click_id: int, newsletter_id: int, queue_id: int, time: string}>
   */
  private function generateOpensAndClicks(array $sentEmails, array $allSubscribers): array {
    $clicksForRevenue = [];
    $persistedSinceFlush = 0;

    foreach ($sentEmails as $email) {
      $recipients = $this->getRecipientIdsForEmail($email);
      if (!$recipients) {
        continue;
      }

      $openerCount = (int)floor(count($recipients) * $this->config->getOpenRate());
      $openerIds = $openerCount > 0 ? $this->pickRandomValues($recipients, $openerCount) : [];

      $clickerCount = (int)floor(count($openerIds) * $this->config->getClickRate());
      $clickerIds = $clickerCount > 0 ? $this->pickRandomValues($openerIds, $clickerCount) : [];
      $clickerLookup = array_flip($clickerIds);

      $newsletterRef = $this->entityManager->getReference(NewsletterEntity::class, $email['newsletter_id']);
      $queueRef = $this->entityManager->getReference(SendingQueueEntity::class, $email['queue_id']);
      $linkRef = $this->entityManager->getReference(NewsletterLinkEntity::class, $email['link_id']);

      foreach ($openerIds as $subscriberId) {
        $subscriber = $allSubscribers[$subscriberId] ?? null;
        if (!$subscriber) {
          continue;
        }

        $openedAt = $this->capToPast((new Carbon())->setTimestamp($email['sent_at'])->addMinutes(random_int(15, 72 * 60)));
        $open = new StatisticsOpenEntity($newsletterRef, $queueRef, $subscriber);
        $open->setCreatedAt($openedAt);
        $this->entityManager->persist($open);
        $this->bumpLastAt($subscriber, 'lastOpenAt', $openedAt);
        $this->bumpLastAt($subscriber, 'lastEngagementAt', $openedAt);

        if (isset($clickerLookup[$subscriberId])) {
          $clickedAt = $this->capToPast((clone $openedAt)->addMinutes(random_int(1, 240)));
          $click = new StatisticsClickEntity($newsletterRef, $queueRef, $subscriber, $linkRef, random_int(1, 3));
          $click->setCreatedAt($clickedAt);
          $click->setUpdatedAt($clickedAt);
          $this->entityManager->persist($click);
          $this->bumpLastAt($subscriber, 'lastClickAt', $clickedAt);
          $this->bumpLastAt($subscriber, 'lastEngagementAt', $clickedAt);
          $this->entityManager->flush();

          $clicksForRevenue[] = [
            'subscriber_id' => $subscriberId,
            'click_id' => $click->getId(),
            'newsletter_id' => $email['newsletter_id'],
            'queue_id' => $email['queue_id'],
            'time' => $clickedAt->toDateTimeString(),
          ];
        }

        $persistedSinceFlush++;
        if ($persistedSinceFlush >= self::BULK_INSERT_BATCH_SIZE) {
          $this->entityManager->flush();
          $persistedSinceFlush = 0;
        }
      }
    }
    $this->entityManager->flush();
    return $clicksForRevenue;
  }

  /**
   * @param array<int, array{subscriber_id: int, click_id: int, newsletter_id: int, queue_id: int, time: string}> $clicksForRevenue
   * @param array<int, SubscriberEntity> $allSubscribers
   * @param array<int, \WC_Product> $products
   */
  private function generateRevenue(array $clicksForRevenue, array $allSubscribers, array $products): int {
    if (!$products || $this->config->getPurchaseRate() <= 0) {
      return 0;
    }

    $clicksBySubscriber = [];
    foreach ($clicksForRevenue as $click) {
      $clicksBySubscriber[$click['subscriber_id']][] = $click;
    }

    $subscriberIds = array_keys($clicksBySubscriber);
    shuffle($subscriberIds);
    $buyerCount = (int)floor(count($subscriberIds) * $this->config->getPurchaseRate());
    $buyerIds = array_slice($subscriberIds, 0, $buyerCount);

    $orderCount = 0;
    foreach ($buyerIds as $subscriberId) {
      $subscriber = $allSubscribers[$subscriberId] ?? null;
      if (!$subscriber) {
        continue;
      }

      $customerId = $this->getOrCreateCustomerId($subscriber);
      if ($customerId) {
        $subscriber->setIsWoocommerceUser(true);
        $subscriber->setWpUserId($customerId);
      }

      $ordersForSubscriber = random_int($this->config->getMinOrdersPerBuyer(), $this->config->getMaxOrdersPerBuyer());
      $subscriberClicks = $clicksBySubscriber[$subscriberId];
      for ($i = 0; $i < $ordersForSubscriber; $i++) {
        $clickData = $subscriberClicks[array_rand($subscriberClicks)];
        $completedAt = $this->capToPast((new Carbon($clickData['time']))->addMinutes(random_int(60, 72 * 60)));
        $orderProducts = $this->pickRandomValues($products, random_int(1, min(2, count($products))));
        $order = $this->createCompletedOrder($subscriber, $customerId ?: null, $orderProducts, $completedAt);
        if (!$order instanceof \WC_Order) {
          continue;
        }
        $this->trackOrderRevenue($subscriber, $clickData, $order, $completedAt);
        $this->bumpLastAt($subscriber, 'lastPurchaseAt', $completedAt);
        $this->bumpLastAt($subscriber, 'lastEngagementAt', $completedAt);
        $orderCount++;
      }
      $this->entityManager->flush();
    }
    return $orderCount;
  }

  /**
   * @return AutomationData[]
   */
  private function createAutomations(string $runSuffix): array {
    $automations = [];
    for ($i = 1; $i <= $this->config->getAutomationsCount(); $i++) {
      $createdAt = $this->randomPastDate();
      $email = (new NewsletterFactory())
        ->withSubject(sprintf('[%s automation email %d %s]', $this->config->getPrefix(), $i, $runSuffix))
        ->withAutomationType()
        ->withActiveStatus()
        ->withCreatedAt($createdAt->toDateTimeString())
        ->create();

      $automations[] = (new AutomationFactory())
        ->withName(sprintf('%s automation %d (%s)', $this->config->getPrefix(), $i, $runSuffix))
        ->withCreatedAt(new \DateTimeImmutable($createdAt->toDateTimeString()))
        ->withSomeoneSubscribesTrigger()
        ->withDelayAction()
        ->withSendEmailStep($email)
        ->withStatusActive()
        ->create();
    }
    return $automations;
  }

  /**
   * @param AutomationData[] $automations
   * @param array<int, SubscriberEntity> $allSubscribers
   */
  private function createAutomationRuns(array $automations, array $allSubscribers): int {
    if (!$automations || !$allSubscribers || $this->config->getAutomationRunsCount() === 0) {
      return 0;
    }

    $subscriberIds = array_keys($allSubscribers);
    $created = 0;
    for ($i = 1; $i <= $this->config->getAutomationRunsCount(); $i++) {
      $automation = $automations[($i - 1) % count($automations)];
      $trigger = $this->getFirstTrigger($automation);
      if (!$trigger instanceof Step) {
        continue;
      }

      $subscriberId = $subscriberIds[array_rand($subscriberIds)];
      $createdAt = $this->randomPastDateTimeImmutable();
      $status = $this->pickAutomationRunStatus();
      $run = (new AutomationRunFactory())
        ->withAutomation($automation)
        ->withTriggerKey($trigger->getKey())
        ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]))
        ->withStatus($status)
        ->withCreatedAt($createdAt)
        ->withUpdatedAt($createdAt)
        ->create();

      $this->createAutomationRunLogs($automation, $run, $status, $createdAt);
      $created++;
    }
    return $created;
  }

  private function createAutomationRunLogs(AutomationData $automation, AutomationRunData $run, string $runStatus, \DateTimeImmutable $createdAt): void {
    $steps = array_values(array_filter($automation->getSteps(), function(Step $step): bool {
      return $step->getType() !== Step::TYPE_ROOT;
    }));
    $lastStepIndex = count($steps) - 1;

    foreach ($steps as $index => $step) {
      $status = AutomationRunLogData::STATUS_COMPLETE;
      if ($runStatus === AutomationRunData::STATUS_FAILED && $index === $lastStepIndex) {
        $status = AutomationRunLogData::STATUS_FAILED;
      } elseif ($runStatus === AutomationRunData::STATUS_RUNNING && $index === $lastStepIndex) {
        $status = AutomationRunLogData::STATUS_RUNNING;
      }

      $factory = (new AutomationRunLogFactory($run->getId(), $step))->setStatus($status);
      if ($status === AutomationRunLogData::STATUS_FAILED) {
        $factory = $factory->withError(new \RuntimeException('Generated sample automation failure'));
      }
      $log = $factory->create();
      $this->backdateAutomationRunLog((int)$log->getId(), $createdAt, $index);
    }
  }

  /**
   * @param \WC_Product[] $products
   */
  private function createCompletedOrder(SubscriberEntity $subscriber, ?int $customerId, array $products, Carbon $completedAt): ?\WC_Order {
    $args = [];
    if ($customerId) {
      $args['customer_id'] = $customerId;
    }
    $order = wc_create_order($args);
    if (!$order instanceof \WC_Order) {
      return null;
    }
    $address = [
      'first_name' => $subscriber->getFirstName() ?: 'Sample',
      'last_name' => $subscriber->getLastName() ?: 'Subscriber',
      'email' => $subscriber->getEmail(),
      'address_1' => '123 Sample st.',
      'city' => 'Sample City',
      'postcode' => '12345',
      'country' => $this->pickRandomValues(['FR', 'GB', 'US', 'IE', 'IT'], 1)[0],
    ];
    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');
    foreach ($products as $product) {
      $order->add_product($product, random_int(1, 3));
    }
    $order->calculate_totals();
    $order->set_date_created(get_gmt_from_date((clone $completedAt)->subMinute()->toDateTimeString()));
    $order->set_date_completed($completedAt->toDateTimeString());
    $order->set_date_paid($completedAt->toDateTimeString());
    $order->update_status('completed', '', false);
    $order->save();
    return $order;
  }

  /**
   * @param array{subscriber_id: int, click_id: int, newsletter_id: int, queue_id: int, time: string} $clickData
   */
  private function trackOrderRevenue(SubscriberEntity $subscriber, array $clickData, \WC_Order $order, Carbon $completedAt): void {
    $newsletter = $this->entityManager->getReference(NewsletterEntity::class, $clickData['newsletter_id']);
    $queue = $this->entityManager->getReference(SendingQueueEntity::class, $clickData['queue_id']);
    $click = $this->entityManager->getReference(StatisticsClickEntity::class, $clickData['click_id']);
    $purchase = new StatisticsWooCommercePurchaseEntity(
      $newsletter,
      $queue,
      $click,
      $order->get_id(),
      $order->get_currency(),
      (float)$order->get_total(),
      $order->get_status()
    );
    $purchase->setSubscriber($subscriber);
    $purchase->setCreatedAt($completedAt);
    $this->entityManager->persist($purchase);
  }

  private function getOrCreateCustomerId(SubscriberEntity $subscriber): int {
    if (!function_exists('wc_create_new_customer')) {
      return 0;
    }
    $customerId = wc_create_new_customer(
      $subscriber->getEmail(),
      $subscriber->getEmail(),
      wp_generate_password(12, false)
    );
    if ($customerId instanceof \WP_Error) {
      $existing = get_user_by('email', $subscriber->getEmail());
      return $existing ? (int)$existing->ID : 0;
    }
    return (int)$customerId;
  }

  /**
   * @param int[] $listIds
   * @param array<int, int[]> $subscribersByList
   * @return int[]
   */
  private function getRecipientsForLists(array $listIds, array $subscribersByList): array {
    $recipientIds = [];
    foreach ($listIds as $listId) {
      foreach ($subscribersByList[$listId] ?? [] as $subscriberId) {
        $recipientIds[$subscriberId] = true;
      }
    }
    return array_keys($recipientIds);
  }

  /**
   * @return int[]
   */
  private function getRecipientIdsForEmail(array $email): array {
    $connection = $this->entityManager->getConnection();
    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $result = $connection->executeQuery(
      "SELECT subscriber_id FROM $table WHERE queue_id = :queueId",
      ['queueId' => $email['queue_id']]
    )->fetchFirstColumn();
    return array_map('intval', $result);
  }

  private function getFirstTrigger(AutomationData $automation): ?Step {
    foreach ($automation->getTriggers() as $trigger) {
      return $trigger;
    }
    return null;
  }

  private function backdateAutomationRunLog(int $logId, \DateTimeImmutable $runCreatedAt, int $stepOffset): void {
    global $wpdb;
    $startedAt = $runCreatedAt->modify('+' . $stepOffset . ' minutes');
    $updatedAt = $startedAt->modify('+30 seconds');
    $table = $wpdb->prefix . 'mailpoet_automation_run_logs';
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE `$table` SET `started_at` = :startedAt, `updated_at` = :updatedAt WHERE `id` = :id",
      [
        'startedAt' => $startedAt->format('Y-m-d H:i:s'),
        'updatedAt' => $updatedAt->format('Y-m-d H:i:s'),
        'id' => $logId,
      ]
    );
  }

  /**
   * @return array|false|\WP_Term
   */
  private function createProductCategory(string $name, string $slug) {
    $term = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
    if ($term instanceof \WP_Error) {
      return get_term_by('slug', $slug, 'product_cat');
    }
    return get_term_by('slug', $slug, 'product_cat');
  }

  private function bumpLastAt(SubscriberEntity $subscriber, string $field, \DateTimeInterface $candidate): void {
    $setter = 'set' . ucfirst($field);
    $getter = 'get' . ucfirst($field);
    if (!method_exists($subscriber, $setter) || !method_exists($subscriber, $getter)) {
      return;
    }
    $current = $subscriber->$getter();
    if (!$current instanceof \DateTimeInterface || $candidate > $current) {
      $subscriber->$setter($candidate);
    }
  }

  private function pickStatusByWeight(): string {
    $total = array_sum(self::STATUS_DISTRIBUTION);
    $roll = random_int(1, $total);
    $cursor = 0;
    foreach (self::STATUS_DISTRIBUTION as $status => $weight) {
      $cursor += $weight;
      if ($roll <= $cursor) {
        return $status;
      }
    }
    return SubscriberEntity::STATUS_SUBSCRIBED;
  }

  private function pickAutomationRunStatus(): string {
    $total = array_sum(self::AUTOMATION_RUN_STATUS_DISTRIBUTION);
    $roll = random_int(1, $total);
    $cursor = 0;
    foreach (self::AUTOMATION_RUN_STATUS_DISTRIBUTION as $status => $weight) {
      $cursor += $weight;
      if ($roll <= $cursor) {
        return $status;
      }
    }
    return AutomationRunData::STATUS_COMPLETE;
  }

  /**
   * @template T
   * @param array<mixed, T> $pool
   * @return array<int, T>
   */
  private function pickRandomElements(array $pool, int $count): array {
    if ($count <= 0 || !$pool) {
      return [];
    }
    $count = min($count, count($pool));
    $keys = array_rand($pool, $count);
    $keys = is_array($keys) ? $keys : [$keys];
    return array_values(array_map(function($key) use ($pool) {
      return $pool[$key];
    }, $keys));
  }

  /**
   * @template T
   * @param array<int, T> $values
   * @return array<int, T>
   */
  private function pickRandomValues(array $values, int $count): array {
    return $this->pickRandomElements($values, $count);
  }

  private function randomPastDate(): Carbon {
    $daysAgo = random_int($this->config->getMinDaysAgo(), $this->config->getMaxDaysAgo());
    return (new Carbon())->subDays($daysAgo)->subMinutes(random_int(0, 1440));
  }

  private function randomPastDateTimeImmutable(): \DateTimeImmutable {
    return new \DateTimeImmutable($this->randomPastDate()->toDateTimeString());
  }

  private function capToPast(Carbon $date): Carbon {
    $latest = (new Carbon())->subMinutes(random_int(1, 120));
    if ($date > $latest) {
      return $latest;
    }
    return $date;
  }

  private function getRandomString(int $length = 5): string {
    return substr(bin2hex(random_bytes($length)), 0, $length); // phpcs:ignore
  }
}
