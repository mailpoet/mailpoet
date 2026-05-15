<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use Codeception\Util\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewslettersResponseBuilderTest extends \MailPoetTest {
  public function testItBuildsStats() {
    $di = ContainerWrapper::getInstance();
    $em = $di->get(EntityManager::class);
    $em->persist($newsletter = new NewsletterEntity);
    $newsletter->setSubject('Response Builder Test');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $em->flush();
    $stats = [
      'total_sent' => 10,
      'children_count' => 3,
      'statistics' => [
        'opened' => 6,
        'clicked' => 4,
        'unsubscribed' => 2,
        'bounced' => 1,
        'machineOpened' => 9,
        'revenue' => null,
        'unsubscribeReasons' => [],
      ],
    ];
    $statistics = new NewsletterStatistics(4, 6, 2, 1, 10, null);
    $statistics->setMachineOpenCount(9);
    $newsletterStatsRepository = Stub::make(NewsletterStatisticsRepository::class, [
      'getTotalSentCount' => $stats['total_sent'],
      'getChildrenCount' => $stats['children_count'],
      'getStatistics' => $statistics,
    ]);
    $newsletterRepository = Stub::make(NewslettersRepository::class);
    $newsletterUrl = $this->diContainer->get(Url::class);
    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $logRepository = $this->diContainer->get(LogRepository::class);
    $shareVisibility = $this->diContainer->get(ShareVisibility::class);
    $statisticsUnsubscribesRepository = Stub::make(StatisticsUnsubscribesRepository::class, [
      'getReasonCountsForNewsletter' => [],
    ]);
    $responseBuilder = new NewslettersResponseBuilder(
      $em,
      $newsletterRepository,
      $newsletterStatsRepository,
      $newsletterUrl,
      $sendingQueuesRepository,
      $logRepository,
      $shareVisibility,
      $statisticsUnsubscribesRepository
    );
    $response = $responseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_CHILDREN_COUNT,
      NewslettersResponseBuilder::RELATION_TOTAL_SENT,
      NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    verify($response['total_sent'])->equals($stats['total_sent']);
    verify($response['children_count'])->equals($stats['children_count']);
    verify($response['statistics'])->equals($stats['statistics']);
    $em->remove($newsletter);
    $em->flush();
  }

  public function testItReplacesPersonalizationTags() {
    $em = $this->diContainer->get(EntityManager::class);
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $em->persist($newsletter = new NewsletterEntity);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $newsletter->setSubject('Subject');
    $em->flush();

    $newsletter->setSubject('Subject');
    $response = $responseBuilder->buildForListing([$newsletter]);
    verify($response[0]['subject'])->equals('Subject');

    $newsletter->setSubject('Hello <!--[mailpoet/subscriber-firstname default="subscriber"]-->!');
    $response = $responseBuilder->buildForListing([$newsletter]);
    verify($response[0]['subject'])->equals('Hello [mailpoet/subscriber-firstname default="subscriber"]!');
  }

  public function testItReplacesPersonalizationTagsInSentEmail() {
    $em = $this->diContainer->get(EntityManager::class);
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $em->persist($newsletter = new NewsletterEntity);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $newsletter->setSubject('Subject');
    $em->persist($task = new ScheduledTaskEntity());
    $em->persist($queue = new SendingQueueEntity());
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $queue->setNewsletterRenderedSubject('Hello <!--[mailpoet/subscriber-firstname default="subscriber"]-->!');
    $em->flush();
    $response = $responseBuilder->buildForListing([$newsletter]);
    /** @var string[] $renderedQueue */
    $renderedQueue = $response[0]['queue'];
    verify($renderedQueue['newsletter_rendered_subject'])->equals('Hello [mailpoet/subscriber-firstname default="subscriber"]!');
  }

  public function testItAggregatesTimeZoneCampaignQueueForListing() {
    $em = $this->diContainer->get(EntityManager::class);
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $campaignId = 'timezone-campaign';
    $em->persist($newsletter = new NewsletterEntity);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $newsletter->setSubject('Subject');

    $groups = [
      ['Europe/Bratislava', '2018-10-10 10:00:00', 1],
      ['America/New_York', '2018-10-11 10:00:00', 2],
    ];
    foreach ($groups as [$timeZone, $scheduledAt, $count]) {
      $em->persist($task = new ScheduledTaskEntity());
      $task->setType(\MailPoet\Cron\Workers\SendingQueue\SendingQueue::TASK_TYPE);
      $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
      $task->setScheduledAt(new \DateTimeImmutable($scheduledAt));
      $em->persist($queue = new SendingQueueEntity());
      $queue->setNewsletter($newsletter);
      $queue->setTask($task);
      $queue->setCountTotal($count);
      $queue->setCountToProcess($count);
      $queue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
        TimeZoneCampaignScheduler::META_GROUP_TIMEZONE => $timeZone,
        TimeZoneCampaignScheduler::META_FALLBACK_USED => false,
      ]);
    }
    $em->flush();

    $response = $responseBuilder->buildForListing([$newsletter]);

    /** @var array<string, mixed> $queue */
    $queue = $response[0]['queue'];
    verify($queue['scheduled_at'])->equals('2018-10-10 10:00:00');
    verify($queue['count_total'])->equals('3');
    verify($queue['count_to_process'])->equals('3');
    $this->assertIsArray($queue['meta']);
    $this->assertIsArray($queue['meta'][TimeZoneCampaignScheduler::META_TIMEZONE_BREAKDOWN]);
    verify(count($queue['meta'][TimeZoneCampaignScheduler::META_TIMEZONE_BREAKDOWN]))->equals(2);
  }

  public function testItAddsSharingDataToListingItems() {
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $newsletterUrl = $this->diContainer->get(Url::class);
    $newsletter = (new Newsletter())
      ->withSubject('Share me')
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();

    $response = $responseBuilder->buildForListing([$newsletter]);

    verify($response[0]['share_url'])->equals($newsletterUrl->getPublicShareUrl($newsletter));
    verify($response[0]['share_visibility'])->equals(ShareVisibility::VISIBILITY_PRIVATE);
    verify($response[0]['effective_share_visibility'])->equals(ShareVisibility::VISIBILITY_PRIVATE);
    verify($response[0]['can_share'])->false();
    verify($response[0]['is_share_supported'])->true();
    verify($response[0]['share_unavailable_reason'])->equals('Sharing is turned off for this email.');
  }

  public function testItAddsSharingDataToEmailResponse() {
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $newsletterUrl = $this->diContainer->get(Url::class);
    $newsletter = (new Newsletter())
      ->withSubject('Share me')
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();

    $response = $responseBuilder->build($newsletter);

    verify($response['share_url'])->equals($newsletterUrl->getPublicShareUrl($newsletter));
    verify($response['share_visibility'])->equals(ShareVisibility::VISIBILITY_PUBLIC);
    verify($response['effective_share_visibility'])->equals(ShareVisibility::VISIBILITY_PUBLIC);
    verify($response['can_share'])->true();
    verify($response['is_share_supported'])->true();
    verify($response['share_unavailable_reason'])->equals('');
  }

  public function testItExplainsUnsupportedSharing() {
    $responseBuilder = $this->diContainer->get(NewslettersResponseBuilder::class);
    $newsletter = (new Newsletter())
      ->withSubject('Draft')
      ->withDraftStatus()
      ->create();

    $response = $responseBuilder->buildForListing([$newsletter]);

    verify($response[0]['share_url'])->equals('');
    verify($response[0]['can_share'])->false();
    verify($response[0]['is_share_supported'])->false();
    verify($response[0]['share_unavailable_reason'])->equals('Only sent emails can be shared.');
  }
}
