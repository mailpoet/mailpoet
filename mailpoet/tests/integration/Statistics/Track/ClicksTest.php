<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
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
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\AutomationRun as AutomationRunFactory;
use MailPoet\Util\Cookies;
use MailPoet\Util\Request;
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
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class)
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);

    verify($this->statisticsClicksRepository->findAll())->empty();
    verify($this->statisticsOpensRepository->findAll())->empty();
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);

    verify($this->statisticsClicksRepository->findAll())->notEmpty();
    verify($this->statisticsOpensRepository->findAll())->notEmpty();
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals('User Agent');
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals('User Agent');
    $data->userAgent = 'User Agent 2';
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals('User Agent 2');
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    // Track Human User Agent
    $data = $this->trackData;
    $humanUserAgentName = 'Human user Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals($humanUserAgentName);
    verify($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals($humanUserAgentName);
    verify($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    // Track Machine User Agent
    $data = $this->trackData;
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals($machineUserAgentName);
    verify($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    // Track Human User Agent
    $humanUserAgentName = 'Human user Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals($humanUserAgentName);
    verify($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $data = $this->trackData;
    // Track Unknown User Agent
    $data->userAgent = null;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    verify($click->getUserAgent())->null();
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $data->userAgent = $machineUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    verify($click->getUserAgent())->null();
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $data = $this->trackData;
    // Track Unknown User Agent
    $data->userAgent = null;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    verify($click->getUserAgent())->null();
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $humanUserAgentName = 'User Agent';
    $data->userAgent = $humanUserAgentName;
    $clicks->track($data);
    $trackedClicks = $clicksRepository->findAll();
    verify($trackedClicks)->arrayCount(1);
    $click = $trackedClicks[0];
    $userAgent = $click->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    verify($userAgent->getUserAgent())->equals($humanUserAgentName);
    verify($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    verify($click->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($this->trackData);
  }

  public function testItDoesNotTrackClickWithoutConsentButStillRedirects() {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($this->trackData);
    $this->assertCount(0, $this->entityManager->getRepository(StatisticsClickEntity::class)->findAll());
    $this->assertCount(0, $this->entityManager->getRepository(StatisticsOpenEntity::class)->findAll());
    $this->assertNull($this->subscriber->getLastClickAt());
  }

  public function testClickingTheOptOutLinkIsNotRecordedButStillRedirects() {
    // The one link whose whole purpose is to stop tracking must never be the
    // thing that records a click. The subscriber here is fully trackable, so
    // the only reason nothing is recorded is the link itself.
    $optOutLink = new NewsletterLinkEntity(
      $this->newsletter,
      $this->queue,
      Clicks::TRACKING_OPT_OUT_SHORTCODE,
      'opt-out-hash'
    );
    $this->entityManager->persist($optOutLink);
    $this->entityManager->flush();
    $trackData = clone $this->trackData;
    $trackData->link = $optOutLink;
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
    ], [
      // The redirect still happens: this is about not recording, not about
      // breaking the link.
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($trackData);
    $this->assertCount(0, $this->entityManager->getRepository(StatisticsClickEntity::class)->findAll());
    $this->assertNull($this->subscriber->getLastClickAt());
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($this->trackData);

    verify($this->statisticsClicksRepository->findAll()[0]->getCount())->equals(1);
    $clicks->track($this->trackData);
    verify($this->statisticsClicksRepository->findAll()[0]->getCount())->equals(2);
  }

  public function testItConvertsShortcodesToUrl() {
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->stringContainsString('&endpoint=view_in_browser');
  }

  public function testItAddsMethodForPostRequestsToShortCodes() {
    $requestMock = $this->createMock(Request::class);
    $requestMock->method('isPost')->willReturn(true);
    $this->clicks = $this->getServiceWithOverrides(Clicks::class, ['request' => $requestMock]);
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->stringContainsString('&request_method=POST');
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
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

  public function testItResolvesPersonalizationTagTokenUrls() {
    $link = $this->clicks->processUrl(
      '[mailpoet/subscription-unsubscribe-url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->stringContainsString('action=confirm_unsubscribe');
  }

  /**
   * @group woo
   */
  public function testItResolvesOrderContextTokenUrlsRegisteredForTheAutomationRun() {
    $order = $this->tester->createWooCommerceOrder();
    $run = (new AutomationRunFactory())
      ->withSubject(new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]))
      ->create();
    $this->queue->setMeta(['automation' => ['run_id' => $run->getId()]]);
    $this->entityManager->flush();
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $wp = WPFunctions::get();
    // Mirrors how the order review URL tag is registered: only once the run's subjects are known
    $wp->addAction('mailpoet_automation_email_extend_personalization_tags_for_sending', function () use ($registry) {
      $registry->register(new Personalization_Tag('Order URL', 'acme/order-url', 'Order', function (array $context): string {
        return 'https://example.com/order/' . $context['order']->get_id();
      }));
    });

    try {
      $link = $this->clicks->processUrl(
        '[acme/order-url]',
        $this->newsletter,
        $this->subscriber,
        $this->queue,
        $preview = false
      );
    } finally {
      $wp->removeAllActions('mailpoet_automation_email_extend_personalization_tags_for_sending');
      $registry->unregister('[acme/order-url]');
      $this->tester->deleteTestWooOrder($order->get_id());
    }

    verify($link)->equals('https://example.com/order/' . $order->get_id());
  }

  public function testItAddsMethodForPostRequestsToPersonalizationTagTokenUrls() {
    $requestMock = $this->createMock(Request::class);
    $requestMock->method('isPost')->willReturn(true);
    $this->clicks = $this->getServiceWithOverrides(Clicks::class, ['request' => $requestMock]);
    $link = $this->clicks->processUrl(
      '[mailpoet/subscription-unsubscribe-url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->stringContainsString('&request_method=POST');
  }

  public function testItAppendsMethodForPostRequestsBeforeTheFragment() {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $registry->register(new Personalization_Tag('Anchor URL', 'acme/anchor-url', 'Test', function (): string {
      return 'https://example.com/page?a=1#section';
    }));
    $requestMock = $this->createMock(Request::class);
    $requestMock->method('isPost')->willReturn(true);
    $this->clicks = $this->getServiceWithOverrides(Clicks::class, ['request' => $requestMock]);

    try {
      $link = $this->clicks->processUrl(
        '[acme/anchor-url]',
        $this->newsletter,
        $this->subscriber,
        $this->queue,
        $preview = false
      );
    } finally {
      $registry->unregister('[acme/anchor-url]');
    }

    verify($link)->equals('https://example.com/page?a=1&request_method=POST#section');
  }

  public function testItRecordsClickAndAbortsWhenPersonalizationTagTokenUrlCannotBeResolved() {
    $link = new NewsletterLinkEntity($this->newsletter, $this->queue, '[acme/dead-url]', 'tokenhash');
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $this->trackData->link = $link;
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'abort' => Expected::exactly(1),
      'redirectToUrl' => null,
    ], $this);
    try {
      $registry->register(new Personalization_Tag('Dead URL', 'acme/dead-url', 'Test', function (): string {
        return '';
      }));
      $clicks->track($this->trackData);
    } finally {
      $registry->unregister('[acme/dead-url]');
    }

    // the click is recorded before the redirect is attempted
    verify($this->statisticsClicksRepository->findAll())->arrayCount(1);
  }

  public function testItPassesArgumentsToCustomShortcodesInUrls() {
    // This test verifies the full integration: Clicks::processUrl() -> Link::processShortcodeAction()
    // with arguments being properly passed through to the filter
    remove_all_filters('mailpoet_newsletter_shortcode_link');
    $argumentsReceived = null;
    add_filter('mailpoet_newsletter_shortcode_link', function($shortcode, $newsletter, $subscriber, $queue, $arguments, $wpUserPreview) use (&$argumentsReceived) {
      $argumentsReceived = $arguments;
      if ($shortcode === '[link:custom_login]') {
        // Use the arguments to build a dynamic URL
        $token = $arguments['token'] ?? 'default';
        $expires = $arguments['expires'] ?? '30days';
        return "https://example.com/login?token={$token}&expires={$expires}";
      }
      return $shortcode;
    }, 10, 6);

    // Test with WordPress-style arguments (multiple arguments)
    $url = $this->clicks->processUrl(
      '[link:custom_login token="abc123" expires="7days"]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      false
    );

    // Verify arguments were received and processed correctly
    verify($argumentsReceived)->isArray();
    verify($argumentsReceived)->arrayHasKey('token');
    verify($argumentsReceived['token'])->equals('abc123');
    verify($argumentsReceived)->arrayHasKey('expires');
    verify($argumentsReceived['expires'])->equals('7days');
    verify($url)->equals('https://example.com/login?token=abc123&expires=7days');
  }

  public function testItDoesNotConvertNonexistentShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:unknown_shortcode]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->equals('[link:unknown_shortcode]');
  }

  public function testItDoesNotConvertRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->equals('http://example.com');
  }

  public function testItProcessesShortcodesInRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com/?email=[subscriber:email]&newsletter_subject=[newsletter:subject]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    verify($link)->equals('http://example.com/?email=test@example.com&newsletter_subject=Subject');
  }

  public function testItUpdatesSubscriberTimestampsForHumanAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());

    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $data = $this->trackData;
    $data->userAgent = 'User Agent';
    $subscribersRepository = $this->getServiceWithOverrides(
      SubscribersRepository::class,
      [
        'changesNotifier' => new SubscriberChangesNotifier($wpMock),
        'wp' => $wpMock,
      ]
    );
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConsentController::class)
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $savedClickTime = $this->subscriber->getLastClickAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    $this->assertInstanceOf(\DateTimeInterface::class, $savedClickTime);
    $this->assertEqualsWithDelta($savedEngagementTime->getTimestamp(), $now->getTimestamp(), 1);
    $this->assertEqualsWithDelta($savedClickTime->getTimestamp(), $now->getTimestamp(), 1);
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
    $subscribersRepository = $this->getServiceWithOverrides(
      SubscribersRepository::class,
      [
        'changesNotifier' => new SubscriberChangesNotifier($wpMock),
        'wp' => $wpMock,
      ]
    );
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConsentController::class)
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $savedClickTime = $this->subscriber->getLastClickAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    $this->assertInstanceOf(\DateTimeInterface::class, $savedClickTime);
    $this->assertEqualsWithDelta($savedEngagementTime->getTimestamp(), $now->getTimestamp(), 1);
    $this->assertEqualsWithDelta($savedClickTime->getTimestamp(), $now->getTimestamp(), 1);
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
    $subscribersRepository = $this->getServiceWithOverrides(
      SubscribersRepository::class,
      [
        'changesNotifier' => new SubscriberChangesNotifier($wpMock),
        'wp' => $wpMock,
      ]
    );
    $statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $opens = new Opens(
      $statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $subscribersRepository,
      $this->diContainer->get(TrackingConsentController::class)
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $savedClickTime = $this->subscriber->getLastClickAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    $this->assertInstanceOf(\DateTimeInterface::class, $savedClickTime);
    $this->assertEqualsWithDelta($savedEngagementTime->getTimestamp(), $now->getTimestamp(), 1);
    $this->assertEqualsWithDelta($savedClickTime->getTimestamp(), $now->getTimestamp(), 1);
  }

  public function testItWontUpdateSubscriberThatWasRecentlyUpdated() {
    $lastClickTime = Carbon::now()->subSeconds(10);
    $clicksRepository = $this->diContainer->get(StatisticsClicksRepository::class);
    $this->subscriber->setLastClickAt($lastClickTime);
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
      $this->diContainer->get(Request::class),
      $this->diContainer->get(TrackingConsentController::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    verify($this->subscriber->getLastClickAt())->equals($lastClickTime);
  }
}
