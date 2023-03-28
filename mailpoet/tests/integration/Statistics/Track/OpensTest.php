<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Statistics\UserAgentsRepository;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class OpensTest extends \MailPoetTest {
  /** @var Opens */
  public $opens;

  /** @var \stdClass */
  public $trackData;

  /** @var SendingQueueEntity */
  public $queue;

  /** @var SubscriberEntity */
  public $subscriber;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  public function _before() {
    parent::_before();
    // create newsletter
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('subject');
    $this->entityManager->persist($newsletter);
    $this->newsletter = $newsletter;
    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $subscriber->setLinkToken('token');
    $this->subscriber = $subscriber;
    $this->entityManager->persist($subscriber);
    // create queue
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $queue->setSubscribers((string)$subscriber->getId());
    $newsletter->getQueues()->add($queue);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();

    $this->queue = $queue;
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'preview' => false,
    ];
    // instantiate class
    $this->statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $this->opens = new Opens(
      $this->statisticsOpensRepository,
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class)
    );
  }

  public function testItReturnsImageWhenTrackDataIsEmpty() {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track(false);
    expect($this->statisticsOpensRepository->findAll())->isEmpty();
  }

  public function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $data->subscriber->setWpUserId(99);
    $data->preview = true;
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    $opens->track($data);
    expect($this->statisticsOpensRepository->findAll())->isEmpty();
  }

  public function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->trackData, $displayImage = false))->isEmpty();
  }

  public function testItTracksOpenEvent() {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    expect($this->statisticsOpensRepository->findAll())->notEmpty();
  }

  public function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    for ($count = 0; $count <= 2; $count++) {
      $opens->track($this->trackData);
    }
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
  }

  public function testItReturnsImageAfterTracking() {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track($this->trackData);
  }

  public function testItSavesNewUserAgent() {
    $this->trackData->userAgent = 'User agent';
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    $opens = $this->statisticsOpensRepository->findAll();
    expect($opens)->count(1);
    $open = $opens[0];
    $userAgent = $open->getUserAgent();
    expect($userAgent)->notNull();
  }

  public function testItSavesOpenWithExistingUserAgent() {
    $this->entityManager->persist(new UserAgentEntity('User agent1'));
    $this->entityManager->flush();
    $this->trackData->userAgent = 'User agent1';
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    $opens = $this->statisticsOpensRepository->findAll();
    expect($opens)->count(1);
    $open = $opens[0];
    $userAgent = $open->getUserAgent();
    expect($userAgent)->notNull();
    $uaRepository = $this->diContainer->get(UserAgentsRepository::class);
    $userAgents = $uaRepository->findBy(['userAgent' => 'User agent1']);
    expect($userAgents)->count(1);
  }

  public function testItOverridesOldUserAgent() {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    $this->trackData->userAgent = 'User agent2';
    $opens->track($this->trackData);
    $this->trackData->userAgent = 'User agent3';
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $opens = $this->statisticsOpensRepository->findAll();
    expect($opens)->count(1);
    $open = $opens[0];
    $userAgent = $open->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals('User agent3');
  }

  public function testItDoesNotOverrideHumanUserAgentWithMachine(): void {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    // Track Human User Agent
    $humanUserAgentName = 'Human User Agent';
    $this->trackData->userAgent = $humanUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    $userAgent = $openEntity->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $this->trackData->userAgent = $machineUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    $userAgent = $openEntity->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItOverridesMachineUserAgentWithHuman(): void {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $this->trackData->userAgent = $machineUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    $userAgent = $openEntity->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($machineUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    // Track Human User Agent
    $humanUserAgentName = 'Human User Agent';
    $this->trackData->userAgent = $humanUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    $userAgent = $openEntity->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItDoesNotOverrideUnknownUserAgentWithMachine(): void {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    // Track Unknown User Agent
    $this->trackData->userAgent = null;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    expect($openEntity->getUserAgent())->null();
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Machine User Agent
    $machineUserAgentName = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $this->trackData->userAgent = $machineUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    expect($openEntity->getUserAgent())->null();
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItOverridesUnknownUserAgentWithHuman(): void {
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
    ], [
      'returnResponse' => null,
    ], $this);
    // Track Unknown User Agent
    $this->trackData->userAgent = null;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    expect($openEntity->getUserAgent())->null();
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    // Track Human User Agent
    $humanUserAgentName = 'User Agent';
    $this->trackData->userAgent = $humanUserAgentName;
    $opens->track($this->trackData);
    expect(count($this->statisticsOpensRepository->findAll()))->equals(1);
    $openEntities = $this->statisticsOpensRepository->findAll();
    expect($openEntities)->count(1);
    $openEntity = reset($openEntities);
    $this->assertInstanceOf(StatisticsOpenEntity::class, $openEntity);
    $userAgent = $openEntity->getUserAgent();
    $this->assertInstanceOf(UserAgentEntity::class, $userAgent);
    expect($userAgent->getUserAgent())->equals($humanUserAgentName);
    expect($userAgent->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    expect($openEntity->getUserAgentType())->equals(UserAgentEntity::USER_AGENT_TYPE_HUMAN);
  }

  public function testItUpdatesSubscriberEngagementForHumanAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->once())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $this->trackData->userAgent = 'User agent';
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock),
    ], [
      'returnResponse' => null,
    ], $this);

    $opens->track($this->trackData);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItUpdatesSubscriberEngagementForUnknownAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->once())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $this->trackData->userAgent = null;
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock),
    ], [
      'returnResponse' => null,
    ], $this);

    $opens->track($this->trackData);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItUpdatesSubscriberEngagementForMachineAgent() {
    $now = Carbon::now();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->once())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $this->trackData->userAgent = UserAgentEntity::MACHINE_USER_AGENTS[0];
    $opens = Stub::construct($this->opens, [
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(UserAgentsRepository::class),
      new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($wpMock), $wpMock),
    ], [
      'returnResponse' => null,
    ], $this);

    $opens->track($this->trackData);
    $savedEngagementTime = $this->subscriber->getLastEngagementAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $savedEngagementTime);
    expect($savedEngagementTime->getTimestamp())->equals($now->getTimestamp());
  }
}
