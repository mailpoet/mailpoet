<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class NewsletterOpensExporterTest extends \MailPoetTest {

  /** @var NewsletterOpensExporter */
  private $exporter;

  public function _before() {
    parent::_before();
    $this->exporter = new NewsletterOpensExporter();
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('data', $result);
    $this->assertEmpty($result['data']);
    $this->assertArrayHasKey('done', $result);
    $this->assertTrue($result['done']);
  }

  public function testExportWorksForSubscriberWithNoNewsletters() {
    Subscriber::createOrUpdate([
      'email' => 'email.that@has.no.newsletters',
    ]);

    $result = $this->exporter->export('email.that@has.no.newsletters');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('data', $result);
    $this->assertEmpty($result['data']);
    $this->assertArrayHasKey('done', $result);
    $this->assertTrue($result['done']);
  }

  public function testExportReturnsData() {
    $userEmail = 'email@with.clicks';
    $userAgentName = 'Mozilla/5.0';
    $this->prepareDataToBeExported($userEmail, $userAgentName);
    $expectedData = [
      ['name' => 'Email subject', 'value' => 'Email Subject'],
      ['name' => 'Timestamp of the open event', 'value' => '2018-01-02 15:16:17'],
      ['name' => 'User-agent', 'value' => $userAgentName],
    ];

    $result = $this->exporter->export($userEmail);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('data', $result);
    $this->assertCount(1, $result['data']);
    $this->assertTrue($result['done']);
    $this->assertArrayHasKey('group_id', $result['data'][0]);
    $this->assertArrayHasKey('group_label', $result['data'][0]);
    $this->assertArrayHasKey('item_id', $result['data'][0]);
    $this->assertArrayHasKey('data', $result['data'][0]);
    $this->assertSame($expectedData, $result['data'][0]['data']);
  }

  public function testExportReturnsDataWhenUserAgentIsUnknown() {
    $userEmail = 'email@with.clicks';
    $this->prepareDataToBeExported($userEmail);

    $result = $this->exporter->export($userEmail);

    $this->assertIsArray($result['data']);
    $this->assertCount(1, $result['data']);
    $this->assertSame($result['data'][0]['data'][2], ['name' => 'User-agent', 'value' => 'Unknown']);
  }

  protected function prepareDataToBeExported(string $userEmail, string $userAgentName = null) {
    $subscriber = Subscriber::createOrUpdate([
      'email' => $userEmail,
    ]);

    $queue = SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => 1,
      'newsletter_id' => 8,
    ]);

    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD,
    ]);

    if ($userAgentName) {
      $userAgent = new UserAgentEntity($userAgentName);
      $this->entityManager->persist($userAgent);
      $this->entityManager->flush();
      $userAgentId = $userAgent->getId();
    } else {
      $userAgentId = null;
    }

    StatisticsOpens::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'subscriber_id' => $subscriber->id(),
      'queue_id' => $queue->id(),
      'created_at' => '2018-01-02 15:16:17',
      'user_agent_id' => $userAgentId,
    ]);
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(UserAgentEntity::class);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
  }
}
