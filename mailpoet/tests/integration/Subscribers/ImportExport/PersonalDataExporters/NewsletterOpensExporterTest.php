<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Carbon\Carbon;

class NewsletterOpensExporterTest extends \MailPoetTest {

  /** @var NewsletterOpensExporter */
  private $exporter;

  public function _before() {
    parent::_before();
    $this->exporter = $this->diContainer->get(NewsletterOpensExporter::class);
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
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('email.that@has.no.newsletters');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

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
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($userEmail);
    $this->entityManager->persist($subscriber);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Email Subject1');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletter);

    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $queue->setNewsletterRenderedSubject('Email Subject');
    $this->entityManager->persist($queue);

    $statisticsOpens = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $statisticsOpens->setCreatedAt(new Carbon('2018-01-02 15:16:17'));

    if ($userAgentName) {
      $userAgent = new UserAgentEntity($userAgentName);
      $this->entityManager->persist($userAgent);
      $statisticsOpens->setUserAgent($userAgent);
    }

    $this->entityManager->persist($statisticsOpens);
    $this->entityManager->flush();
  }
}
