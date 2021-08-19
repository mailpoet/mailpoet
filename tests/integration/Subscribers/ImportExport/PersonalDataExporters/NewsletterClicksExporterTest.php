<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

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
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithNoNewsletters() {
    Subscriber::createOrUpdate([
      'email' => 'email.that@has.no.newsletters',
    ]);
    $result = $this->exporter->export('email.that@has.no.newsletters');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportReturnsData() {
    $userEmail = 'email@with.clicks';
    $userAgentName = 'Mozilla/5.0';
    $this->prepareDataToBeExported($userEmail, $userAgentName);

    $result = $this->exporter->export($userEmail);
    expect($result['data'])->array();
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
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
    $link = NewsletterLink::createOrUpdate([
      'url' => 'Link url',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'hash' => 'xyz',
    ]);

    if ($userAgentName) {
      $userAgent = new UserAgentEntity($userAgentName);
      $this->entityManager->persist($userAgent);
      $this->entityManager->flush();
      $userAgentId = $userAgent->getId();
    } else {
      $userAgentId = null;
    }

    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'subscriber_id' => $subscriber->id(),
      'link_id' => $link->id(),
      'count' => 1,
      'created_at' => '2018-01-02 15:16:17',
      'user_agent_id' => $userAgentId,
    ]);
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    $this->truncateEntity(UserAgentEntity::class);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
