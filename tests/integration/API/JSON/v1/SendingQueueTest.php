<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SendingQueueTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    parent::_before();
    $this->clean();

    $this->newsletter = new NewsletterEntity();
    $this->newsletter->setSubject('My Standard Newsletter');
    $this->newsletter->setBody(json_decode(Fixtures::get('newsletter_body_template'), true));
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();

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
    $newletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter,
      NewsletterEntity::TYPE_STANDARD,
      $newletterOptions
    );

    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTask = $repo->findOneById($result->data['task_id']);
    assert($scheduledTask instanceof ScheduledTaskEntity);
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $scheduled = $scheduledTask->getScheduledAt();
    assert($scheduled instanceof \DateTimeInterface);
    expect($scheduled->format('Y-m-d H:i:s'))->equals($newletterOptions['scheduledAt']);
    expect($scheduledTask->getType())->equals(Sending::TASK_TYPE);
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $sendingQueue = new SendingQueueAPI(
      Stub::make(SubscribersFeature::class, [
        'check' => true,
      ]),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(SendingQueuesRepository::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(SubscribersFinder::class)
    );
    $res = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
    $res = $sendingQueue->resume(['newsletter_id' => $this->newsletter->getId()]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReschedulesScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $newletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter,
      NewsletterEntity::TYPE_STANDARD,
      $newletterOptions
    );
    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);

    // add scheduled task
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTask = $repo->findOneById($result->data['task_id']);
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $scheduled = $scheduledTask->getScheduledAt();
    assert($scheduled instanceof \DateTimeInterface);
    expect($scheduled->format('Y-m-d H:i:s'))->equals('2018-10-10 10:00:00');

    // update scheduled time
    $newletterOptions = [
      'scheduledAt' => '2018-11-11 11:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter,
      NewsletterEntity::TYPE_STANDARD,
      $newletterOptions
    );
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->entityManager->clear();
    $rescheduledTask = $repo->findOneById($result->data['task_id']);
    assert($rescheduledTask instanceof ScheduledTaskEntity);
    // new task was not created
    expect($rescheduledTask->getId())->equals($scheduledTask->getId());
    // scheduled time was updated
    $scheduled = $rescheduledTask->getScheduledAt();
    assert($scheduled instanceof \DateTimeInterface);
    expect($scheduled->format('Y-m-d H:i:s'))->equals('2018-11-11 11:00:00');
  }

  public function testItRejectsNewsletterWithoutUnsubscribeLink() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setBody(['content' => ['type' => 'container', 'columnLayout' => false, 'orientation' => 'vertical']]);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    $sendingQueue = new SendingQueueAPI(
      $this->diContainer->get(SubscribersFeature::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(SendingQueuesRepository::class),
      Stub::make(Bridge::class, [
        'isMailpoetSendingServiceEnabled' => true,
      ]),
      $this->diContainer->get(SubscribersFinder::class)
    );
    $response = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $response = $response->getData();
    expect($response['errors'][0])->array();
    expect($response['errors'][0]['message'])->stringContainsString('Unsubscribe');
    expect($response['errors'][0]['error'])->stringContainsString('bad_request');
  }

  private function _createOrUpdateNewsletterOptions(NewsletterEntity $newsletter, $newsletterType, $options) {
    $newsletterOptionFieldRepository = $this->diContainer->get(NewsletterOptionFieldsRepository::class);
    $newsletterOptionRepository = $this->diContainer->get(NewsletterOptionsRepository::class);

    foreach ($options as $option => $value) {
      $newsletterOptionField = $newsletterOptionFieldRepository->findOneBy(['name' => $option]);
      if (!$newsletterOptionField instanceof NewsletterOptionFieldEntity) {
        $newsletterOptionField = new NewsletterOptionFieldEntity();
        $newsletterOptionField->setName($option);
        $newsletterOptionField->setNewsletterType($newsletterType);
        $this->entityManager->persist($newsletterOptionField);
      }

      $newsletterOption = $newsletterOptionRepository->findOneBy(['newsletter' => $newsletter, 'optionField' => $newsletterOptionField]);
      if (!$newsletterOption instanceof NewsletterOptionEntity) {
        $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
        $newsletter->getOptions()->add($newsletterOption);
        $this->entityManager->persist($newsletterOption);
      }
      $newsletterOption->setValue($value);
    }
    $this->entityManager->flush();
  }

  public function clean() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
