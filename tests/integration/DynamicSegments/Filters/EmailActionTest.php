<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\Newsletter;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

class EmailActionTest extends \MailPoetTest {
  public $subscriberOpenedNotClicked;
  public $subscriberNotSent;
  public $subscriberNotOpened;
  public $subscriberOpenedClicked;
  public $newsletter;

  public function _before() {
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'newsletter 1',
      'status' => 'sent',
      'type' => Newsletter::TYPE_NOTIFICATION,
    ]);
    $this->subscriberOpenedClicked = Subscriber::createOrUpdate([
      'email' => 'opened_clicked@example.com',
    ]);
    $this->subscriberOpenedNotClicked = Subscriber::createOrUpdate([
      'email' => 'opened_not_clicked@example.com',
    ]);
    $this->subscriberNotOpened = Subscriber::createOrUpdate([
      'email' => 'not_opened@example.com',
    ]);
    $this->subscriberNotSent = Subscriber::createOrUpdate([
      'email' => 'not_sent@example.com',
    ]);
    StatisticsNewsletters::createMultiple([
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberOpenedClicked->id, 'queue_id' => 1],
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberOpenedNotClicked->id, 'queue_id' => 1],
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberNotOpened->id, 'queue_id' => 1],
    ]);
    StatisticsOpens::getOrCreate($this->subscriberOpenedClicked->id, $this->newsletter->id, 1);
    StatisticsOpens::getOrCreate($this->subscriberOpenedNotClicked->id, $this->newsletter->id, 1);
    StatisticsClicks::createOrUpdateClickCount(1, $this->subscriberOpenedClicked->id, $this->newsletter->id, 1);
  }

  public function testGetOpened() {
    $emailAction = new EmailAction(EmailAction::ACTION_OPENED, $this->newsletter->id);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(2);
  }

  public function testNotOpened() {
    $emailAction = new EmailAction(EmailAction::ACTION_NOT_OPENED, $this->newsletter->id);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(1);
  }

  public function testGetClickedWithoutLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_CLICKED, $this->newsletter->id);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(1);
  }

  public function testGetClickedWithLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_CLICKED, $this->newsletter->id, 1);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(1);
  }

  public function testGetClickedWithWrongLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_CLICKED, $this->newsletter->id, 2);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(0);
  }

  public function testGetNotClickedWithLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id, 1);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(2);
  }

  public function testGetNotClickedWithWrongLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id, 2);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(3);
  }

  public function testGetNotClickedWithoutLink() {
    $emailAction = new EmailAction(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id);
    $sql = $emailAction->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(2);
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    // @phpstan-ignore-next-line
    StatisticsClicks::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    // @phpstan-ignore-next-line
    StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    // @phpstan-ignore-next-line
    StatisticsOpens::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    $this->newsletter->delete();
    $this->subscriberOpenedClicked->delete();
    $this->subscriberOpenedNotClicked->delete();
    $this->subscriberNotOpened->delete();
    $this->subscriberNotSent->delete();
  }
}
