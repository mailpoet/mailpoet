<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SendingQueueTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  /** @var NewsletterOption */
  private $newsletterOptionsFactory;

  public function _before() {
    parent::_before();
    $this->newsletterOptionsFactory = new NewsletterOption();

    $this->newsletter = (new Newsletter())
      ->withSubject('My Standard Newsletter')
      ->withDefaultBody()
      ->create();

    $settings = SettingsController::getInstance();
    $settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCreatesNewScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $newsletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);

    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTask = $repo->findOneById($result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $scheduled = $scheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    expect($scheduled->format('Y-m-d H:i:s'))->equals($newsletterOptions['scheduledAt']);
    expect($scheduledTask->getType())->equals(Sending::TASK_TYPE);
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'subscribersFeature' => Stub::make(SubscribersFeature::class, [
        'check' => true,
      ]),
    ]);
    $res = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
    $res = $sendingQueue->resume(['newsletter_id' => $this->newsletter->getId()]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReschedulesScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $newsletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);
    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);

    // add scheduled task
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTask = $repo->findOneById($result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $scheduled = $scheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    expect($scheduled->format('Y-m-d H:i:s'))->equals('2018-10-10 10:00:00');

    // update scheduled time
    $newsletterOptions = [
      'scheduledAt' => '2018-11-11 11:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->entityManager->clear();
    $rescheduledTask = $repo->findOneById($result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $rescheduledTask);
    // new task was not created
    expect($rescheduledTask->getId())->equals($scheduledTask->getId());
    // scheduled time was updated
    $scheduled = $rescheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    expect($scheduled->format('Y-m-d H:i:s'))->equals('2018-11-11 11:00:00');
  }

  public function testItRejectsInvalidNewsletters() {
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'newsletterValidator' => Stub::make(NewsletterValidator::class, ['validate' => 'some error']),
    ]);
    $response = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()]);
    $response = $response->getData();
    expect($response['errors'][0])->array();
    expect($response['errors'][0]['message'])->stringContainsString('some error');
    expect($response['errors'][0]['error'])->stringContainsString('bad_request');
  }
}
