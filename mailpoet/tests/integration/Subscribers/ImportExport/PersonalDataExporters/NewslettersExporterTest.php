<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class NewslettersExporterTest extends \MailPoetTest {

  /** @var NewslettersExporter */
  private $exporter;

  /*** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->exporter = new NewslettersExporter(
      $this->diContainer->get(Url::class),
      $this->subscribersRepository,
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(NewsletterStatisticsRepository::class)
    );
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithNoNewsletters() {
    (new SubscriberFactory())->withEmail('email.that@has.no.newsletters')->create();
    $result = $this->exporter->export('email.that@has.no.newsletters');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportReturnsRenderedSubjects() {
    $subscriber = (new SubscriberFactory())
      ->withEmail('user@with.newsletters')
      ->create();

    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $this->entityManager->flush();

    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSubject('Newsletter subject')
      ->create();

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setNewsletterRenderedSubject('Email Subject');
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();

    $stat = new StatisticsNewsletterEntity(
      $newsletter,
      $queue,
      $subscriber
    );
    $this->entityManager->persist($stat);
    $this->entityManager->flush();

    $result = $this->exporter->export('user@with.newsletters');
    expect($result['data'])->array();
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    expect($result['data'][0]['data'])->contains(['name' => 'Email subject', 'value' => 'Email Subject']);
  }

  public function testExportReturnsUrl() {
    $subscriber = (new SubscriberFactory())
      ->withEmail('user1@with.newsletters')
      ->create();

    $newsletter = (new NewsletterFactory())
      ->withSubject('Email Subject1')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $this->entityManager->flush();

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setNewsletterRenderedSubject('Email Subject1');
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();

    $stat = new StatisticsNewsletterEntity(
      $newsletter,
      $queue,
      $subscriber
    );
    $this->entityManager->persist($stat);
    $this->entityManager->flush();

    $result = $this->exporter->export('user1@with.newsletters');
    expect($result['data'][0]['data'][3]['name'])->equals('Email preview');
    expect($result['data'][0]['data'][3]['value'])->stringContainsString('mailpoet_router&endpoint=view_in_browser&action=view&data=');
  }

  public function testExportOpens() {

    $subscriber = (new SubscriberFactory())
      ->withEmail('user21@with.newsletters')
      ->create();
    $subscriber2 = (new SubscriberFactory())
      ->withEmail('user22@with.newsletters')
      ->create();

    $newsletter1 = (new NewsletterFactory())
      ->withSubject('Email Subject1')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $newsletter2 = (new NewsletterFactory())
      ->withSubject('Email Subject2')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    $queue1 = new SendingQueueEntity();
    $queue1->setNewsletter($newsletter1);
    $queue1->setNewsletterRenderedSubject('Email Subject1');
    $queue1->setTask($task);
    $this->entityManager->persist($queue1);
    $this->entityManager->flush();
    $this->entityManager->refresh($newsletter1);

    $queue2 = new SendingQueueEntity();
    $queue2->setNewsletter($newsletter1);
    $queue2->setNewsletterRenderedSubject('Email Subject1');
    $queue2->setTask($task);
    $this->entityManager->persist($queue2);
    $this->entityManager->flush();
    $this->entityManager->refresh($newsletter1);

    $stat1 = new StatisticsNewsletterEntity(
      $newsletter1,
      $queue1,
      $subscriber
    );
    $this->entityManager->persist($stat1);
    $this->entityManager->flush();

    $stat11 = new StatisticsNewsletterEntity(
      $newsletter1,
      $queue1,
      $subscriber2
    );
    $this->entityManager->persist($stat11);
    $this->entityManager->flush();

    $stat2 = new StatisticsNewsletterEntity(
      $newsletter2,
      $queue2,
      $subscriber
    );
    $this->entityManager->persist($stat2);
    $this->entityManager->flush();

    $newsletter1Entity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter1->getId());
    $subscriber1Entity = $this->entityManager->getReference(SubscriberEntity::class, $subscriber->getId());
    $subscriber2Entity = $this->entityManager->getReference(SubscriberEntity::class, $subscriber2->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter1Entity);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1Entity);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2Entity);
    $statisticsOpensEntity = (new StatisticsOpensFactory($newsletter1Entity, $subscriber1Entity))->create();
    $statisticsOpensEntity->setCreatedAt(new Carbon('2017-01-02 12:23:45'));
    $this->entityManager->persist($statisticsOpensEntity);
    $this->entityManager->flush();
    (new StatisticsOpensFactory($newsletter1Entity, $subscriber2Entity))->create();

    $result = $this->exporter->export('user21@with.newsletters');
    expect(count($result['data']))->equals(2);
    expect($result['data'][0]['data'])->contains(['name' => 'Opened', 'value' => 'Yes']);
    expect($result['data'][0]['data'])->contains(['name' => 'Opened at', 'value' => '2017-01-02 12:23:45']);
    expect($result['data'][1]['data'])->contains(['name' => 'Opened', 'value' => 'No']);
  }
}
