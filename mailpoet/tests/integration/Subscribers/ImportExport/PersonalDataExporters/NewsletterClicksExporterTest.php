<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Carbon\Carbon;

class NewsletterClicksExporterTest extends \MailPoetTest {

  /** @var NewsletterClicksExporter */
  private $exporter;

  public function _before() {
    parent::_before();
    $this->exporter = $this->diContainer->get(NewsletterClicksExporter::class);
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result['data'])->equals([]);
    verify($result)->arrayHasKey('done');
    verify($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithNoNewsletters() {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('email.that@has.no.newsletters');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $result = $this->exporter->export('email.that@has.no.newsletters');
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result['data'])->equals([]);
    verify($result)->arrayHasKey('done');
    verify($result['done'])->equals(true);
  }

  public function testExportReturnsData() {
    $userEmail = 'email@with.clicks';
    $userAgentName = 'Mozilla/5.0';
    $this->prepareDataToBeExported($userEmail, $userAgentName);

    $result = $this->exporter->export($userEmail);
    expect($result['data'])->array();
    verify($result['data'])->arrayCount(1);
    verify($result['done'])->equals(true);
    verify($result['data'][0])->arrayHasKey('group_id');
    verify($result['data'][0])->arrayHasKey('group_label');
    verify($result['data'][0])->arrayHasKey('item_id');
    verify($result['data'][0])->arrayHasKey('data');
    expect($result['data'][0]['data'])->contains(['name' => 'Email subject', 'value' => 'Email Subject']);
    expect($result['data'][0]['data'])->contains(['name' => 'URL', 'value' => 'Link url']);
    expect($result['data'][0]['data'])->contains(['name' => 'Timestamp of the click event', 'value' => '2018-01-02 15:16:17']);
    expect($result['data'][0]['data'])->contains(['name' => 'User-agent', 'value' => $userAgentName]);
  }

  public function testExportReturnsDataWhenUserAgentIsUnknown() {
    $userEmail = 'email@with.clicks';
    $this->prepareDataToBeExported($userEmail);

    $result = $this->exporter->export($userEmail);

    $this->assertIsArray($result['data']);
    $this->assertCount(1, $result['data']);
    $this->assertSame($result['data'][0]['data'][3], ['name' => 'User-agent', 'value' => 'Unknown']);
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

    $newsletterLink = new NewsletterLinkEntity($newsletter, $queue, 'Link url', 'xyz');
    $this->entityManager->persist($newsletterLink);

    $statisticsClicks = new StatisticsClickEntity($newsletter, $queue, $subscriber, $newsletterLink, 1);
    $statisticsClicks->setCreatedAt(new Carbon('2018-01-02 15:16:17'));

    if ($userAgentName) {
      $userAgent = new UserAgentEntity($userAgentName);
      $this->entityManager->persist($userAgent);
      $statisticsClicks->setUserAgent($userAgent);
    }

    $this->entityManager->persist($statisticsClicks);
    $this->entityManager->flush();
  }
}
