<?php declare(strict_types = 1);

namespace MailPoet\Homepage;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class HomepageDataControllerTest extends \MailPoetTest {
  /** @var HomepageDataController */
  private $homepageDataController;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->homepageDataController = $this->diContainer->get(HomepageDataController::class);
  }

  public function testItFetchesBasicData(): void {
    $data = $this->homepageDataController->getPageData();
    expect($data)->notEmpty();
    expect($data['taskListDismissed'])->false();
    expect($data['productDiscoveryDismissed'])->false();
    expect($data['taskListStatus'])->array();
    expect($data['taskListStatus'])->notEmpty();
    expect($data['productDiscoveryStatus'])->array();
    expect($data['productDiscoveryStatus'])->notEmpty();
    expect($data['wooCustomersCount'])->int();
    expect($data['subscribersCount'])->int();
    expect($data['subscribersStats'])->array();
    expect($data['taskListStatus'])->notEmpty();
  }

  public function testItFetchesSenderTaskListStatus(): void {
    $settings = $this->diContainer->get(SettingsController::class);

    $settings->set('sender', null);
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.address', 'test@email.com');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.name', 'John Doe');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->true();
  }

  public function testItDoesntFetchTaskListStatusWhenTaskListDismissed(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('homepage.task_list_dismissed', true);
    $data = $this->homepageDataController->getPageData();
    expect($data['taskListStatus'])->null();
  }

  public function testItFetchesSubscribersAddedTaskListStatus(): void {
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->false();

    $form = (new Form())->create();
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->true();
    $this->entityManager->remove($form);
    $this->entityManager->flush($form);

    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->false();

    for ($x = 0; $x <= 11; $x++) {
      (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    }
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->true();
  }

  public function testItFetchesProductDiscoveryStatusForWelcomeCampaign(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->false();

    // Not done when welcome newsletter is activated
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->false();

    // Done when welcome newsletter is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->true();
  }

  public function testItFetchesProductDiscoveryStatusSentNewsletters(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Not done when standard newsletter is draft
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Done when standard newsletter is scheduled
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Done when standard newsletter is sent
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Not done when post notification is draft
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Done when post notification is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Done when automatic email active
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();
  }

  public function testItFetchesProductDiscoveryStatusSetUpAbandonedCartEmail(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->false();

    $newsletter = (new Newsletter())
      ->withAutomaticTypeWooCommerceAbandonedCart()
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();

    // Not done when abandoned cart email is draft
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->false();

    // Done when abandoned cart email is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->true();
  }

  public function testItFetchesSubscriberStatsForZeroSubscribers(): void {
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['subscribed'])->equals(0);
    expect($subscribersStats['global']['unsubscribed'])->equals(0);
  }

  public function testItFetchesCorrectGlobalSubscriberStats(): void {
    $thirtyOneDaysAgo = Carbon::now()->subDays(31);
    $twentyNineDaysAgo = Carbon::now()->subDays(29);
    // Old subscribed
    (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    // New subscribed
    (new Subscriber())->withCreatedAt($twentyNineDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    // Unsubscribed long time ago
    $oldUnsubscribed = (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $oldUnsubscribedStats = new StatisticsUnsubscribeEntity(null, null, $oldUnsubscribed);
    $oldUnsubscribedStats->setCreatedAt($thirtyOneDaysAgo);
    $this->entityManager->persist($oldUnsubscribedStats);
    $this->entityManager->flush();
    // Freshly unsubscribed (but created before the period)
    $newUnsubscribed = (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $newUnsubscribedStats = new StatisticsUnsubscribeEntity(null, null, $newUnsubscribed);
    $newUnsubscribedStats->setCreatedAt($twentyNineDaysAgo);
    $this->entityManager->persist($newUnsubscribedStats);
    $this->entityManager->flush();

    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['subscribed'])->equals(1);
    expect($subscribersStats['global']['unsubscribed'])->equals(1);
  }

  public function testCountMultipleGlobalUnsubscribesOfTheSameSubscriberOnlyOnce(): void {
    $thirtyOneDaysAgo = Carbon::now()->subDays(31);
    $twentyNineDaysAgo = Carbon::now()->subDays(29);
    // New subscribed
    (new Subscriber())->withCreatedAt($twentyNineDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    // Freshly unsubscribed (but created before the period)
    $newUnsubscribed = (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $newUnsubscribedStats = new StatisticsUnsubscribeEntity(null, null, $newUnsubscribed);
    $newUnsubscribedStats->setCreatedAt($twentyNineDaysAgo);
    $this->entityManager->persist($newUnsubscribedStats);
    $newUnsubscribedStats = new StatisticsUnsubscribeEntity(null, null, $newUnsubscribed);
    $newUnsubscribedStats->setCreatedAt($twentyNineDaysAgo);
    $this->entityManager->persist($newUnsubscribedStats);
    $this->entityManager->flush();

    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['unsubscribed'])->equals(1);
  }

  public function testItFetchesCorrectGlobalSubscriberChange(): void {
    $thirtyOneDaysAgo = Carbon::now()->subDays(31);
    $twentyNineDaysAgo = Carbon::now()->subDays(29);

    // 10 New Subscribers
    for ($i = 0; $i < 10; $i++) {
      (new Subscriber())->withCreatedAt($twentyNineDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    }
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['changePercent'])->equals(1000);

    // 10 New Subscribers + 5 Old Subscribers
    for ($i = 0; $i < 5; $i++) {
      (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    }
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['changePercent'])->equals(200);

    // 10 New Subscribers + 6 Old Subscribers
    (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['changePercent'])->equals( 166.7);

    // 10 New Subscribers + 6 Old Subscribers + 10 New Unsubscribed
    for ($i = 0; $i < 10; $i++) {
      $unsubscribed = (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
      $this->entityManager->persist(new StatisticsUnsubscribeEntity(null, null, $unsubscribed));
      $this->entityManager->flush();
    }
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['changePercent'])->equals( 0);

    // 10 New Subscribers + 6 Old Subscribers + 11 New Unsubscribed
    $unsubscribed = (new Subscriber())->withCreatedAt($thirtyOneDaysAgo)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $this->entityManager->persist(new StatisticsUnsubscribeEntity(null, null, $unsubscribed));
    $this->entityManager->flush();
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['global']['changePercent'])->equals( -5.9);
  }

  public function testItFetchesCorrectListLevelSubscribedStats(): void {
    $thirtyOneDaysAgo = Carbon::now()->subDays(31);
    $twentyNineDaysAgo = Carbon::now()->subDays(29);
    $segment = (new Segment())->withName('Segment')->create();
    $segment->setAverageEngagementScore(0.5);
    // Subscribed 29 days ago - only this one counts as subscribed on list level
    $newSubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberSegment = $newSubscribed->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setCreatedAt($twentyNineDaysAgo);
    // Old subscribed - ignored because subscribed too far in the past
    $oldSubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberSegment = $oldSubscribed->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setCreatedAt($thirtyOneDaysAgo);
    // Subscribed only on list level - ignored because not subscribed globally
    (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSegments([$segment])
      ->create();
    // Subscribed only on top level - ignored because not subscribed on list level
    (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSegments([$segment])
      ->create();
    $this->entityManager->flush();
    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['lists'][0]['id'])->equals($segment->getId());
    expect($subscribersStats['lists'][0]['name'])->equals($segment->getName());
    expect($subscribersStats['lists'][0]['subscribed'])->equals(1);
    expect($subscribersStats['lists'][0]['unsubscribed'])->equals(0);
    expect($subscribersStats['lists'][0]['averageEngagementScore'])->equals(0.5);
  }

  public function testItFetchesCorrectListLevelUnsubscribedStats(): void {
    $thirtyOneDaysAgo = Carbon::now()->subDays(31);
    $twentyNineDaysAgo = Carbon::now()->subDays(29);
    $segment = (new Segment())->withName('Segment')->create();
    // Unsubscribed 29 days ago - only this one counts as unsubscribed on list level
    $newUnsubscribed = (new Subscriber())
      ->withCreatedAt($twentyNineDaysAgo)
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberSegment = $newUnsubscribed->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setUpdatedAt($twentyNineDaysAgo);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    // Unsubscribed 31 days ago - ignored because unsubscribed too far in the past
    $newUnsubscribed = (new Subscriber())
      ->withCreatedAt($twentyNineDaysAgo)
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberSegment = $newUnsubscribed->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setUpdatedAt($thirtyOneDaysAgo);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $subscribersStats = $this->homepageDataController->getPageData()['subscribersStats'];
    expect($subscribersStats['lists'][0]['id'])->equals($segment->getId());
    expect($subscribersStats['lists'][0]['name'])->equals($segment->getName());
    expect($subscribersStats['lists'][0]['unsubscribed'])->equals(1);
    expect($subscribersStats['lists'][0]['subscribed'])->equals(0);
  }

  private function cleanup(): void {
    $this->truncateEntity(SettingEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(FormEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
  }
}
