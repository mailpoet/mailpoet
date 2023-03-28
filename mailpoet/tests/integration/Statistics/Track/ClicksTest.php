<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Statistics\Track\SubscriberCookie;
use MailPoet\Statistics\UserAgentsRepository;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ClicksTest extends \MailPoetTest {
  /** @var \stdClass */
  public $trackData;

  /** @var NewsletterLinkEntity */
  public $link;

  /** @var SendingQueueEntity */
  public $queue;

  /** @var SubscriberEntity */
  public $subscriber;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var Clicks */
  private $clicks;

  /** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  public function _before() {
    parent::_before();
    // create newsletter
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('Subject');
    $this->newsletter = $newsletter;
    $this->entityManager->persist($newsletter);
    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->subscriber = $subscriber;
    $this->entityManager->persist($subscriber);
    // create queue
    $task = new ScheduledTaskEntity();
    $task->setType('sending');
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $this->queue = $queue;
    $this->entityManager->persist($queue);

    // create link
    $link = new NewsletterLinkEntity($newsletter, $queue, 'url', 'hash');
    $this->link = $link;
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'link' => $link,
      'preview' => false,
    ];

    $this->clicks = new Clicks(
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class)
    );

    $this->statisticsClicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $this->statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
  }

  public function testItAbortsWhenTrackDataIsEmptyOrMissingLink() {
    // abort function should be called twice:
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'abort' => Expected::exactly(2),
    ], $this);
    $data = $this->trackData;
    // 1. when tracking data does not exist
    $clicks->track(null);
    // 2. when link model object is missing
    unset($data->link);
    $clicks->track($data);
  }

  public function testItDoesNotTrackEventsFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $this->subscriber->setWpUserId(99);
    $this->entityManager->flush();
    $data->preview = true;
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);

    expect($this->statisticsClicksRepository->findAll())->isEmpty();
    expect($this->statisticsOpensRepository->findAll())->isEmpty();
  }

  public function testItTracksClickAndOpenEvent() {
    $data = $this->trackData;
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);

    expect($this->statisticsClicksRepository->findAll())->notEmpty();
    expect($this->statisticsOpensRepository->findAll())->notEmpty();
  }

  public function testItTracksUserAgent() {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = 'User Agent';
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals('User Agent');
  }

  public function testItUpdateUserAgent(): void {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = 'User Agent';
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals('User Agent');
    $data->userAgent = 'User Agent 2';
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals('User Agent 2');
  }

  public function testItDoesNotOverrideHumanUserAgentWithMachine(): void {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    // Track Human User Agent
    $data = $this->trackData;
    $humanUserAgentName = 'Human user Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItOverridesMachineUserAgentWithHuman(): void {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    // Track Machine User Agent
    $data = $this->trackData;
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($machineUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    // Track Human User Agent
    $humanUserAgentName = 'Human user Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItDoesNotOverrideUnknownUserAgentWithMachine(): void {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $data = $this->trackData;
    // Track Unknown User Agent
    $data->userAgent = null;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    expect($click->getUserAgent())->null();
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    expect($click->getUserAgent())->null();
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItOverridesUnknownUserAgentWithHuman(): void {
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $data = $this->trackData;
    // Track Unknown User Agent
    $data->userAgent = null;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    expect($click->getUserAgent())->null();
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $humanUserAgentName = 'User Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    expect($trackedClicks)->count(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItRedirectsToUrlAfterTracking() {
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($this->trackData);
  }

  public function testItIncrementsClickEventCount() {
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($this->trackData);

    expect($this->statisticsClicksRepository->findAll()[0]->getCount())->equals(1);
    $clicks->track($this->trackData);
    expect($this->statisticsClicksRepository->findAll()[0]->getCount())->equals(2);
  }

  public function testItConvertsShortcodesToUrl() {
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->stringContainsString('&endpoint=view_in_browser');
  }

  public function testItFailsToConvertsInvalidShortcodeToUrl() {
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'abort' => Expected::exactly(1),
    ], $this);
    // should call abort() method if shortcode action does not exist
    $link = $clicks->processUrl(
      '[link:]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
  }

  public function testItDoesNotConvertNonexistentShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:unknown_shortcode]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('[link:unknown_shortcode]');
  }

  public function testItDoesNotConvertRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com');
  }

  public function testItProcessesShortcodesInRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com/?email=[subscriber:email]&newsletter_subject=[newsletter:subject]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com/?email=test@example.com&newsletter_subject=Subject');
  }

  public function testItUpdatesSubscriberEngagementForHumanAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());

    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = 'User Agent';
    $subscribersRepository = new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock);
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository
    );
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $opens,
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItUpdatesSubscriberEngagementForUnknownAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = null;
    $subscribersRepository = new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock);
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository
    );
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $opens,
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItUpdatesSubscriberEngagementForMachineAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $subscribersRepository = new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock);
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository
    );
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $opens,
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItWontUpdateSubscriberThatWasRecentlyUpdated() {
    $lastEngagement = Carbon::now()->subSeconds(10);
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $this->subscriber->setLastEngagementAt($lastEngagement);
    $data = $this->trackData;
    $data->userAgent = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $clicks = Stub::construct($this->clicks, [
      $this->diContainer->get(Cookies::class),
      $this->diContainer->get(SubscriberCookie::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $clicksRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(TrackingConfig::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    expect($this->subscriber->getLastEngagementAt())->equals($lastEngagement);
  }
}
