<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberCountShortcodeCest {

  const ACTIVE_SUBSCRIBERS_COUNT = 5;
  const INACTIVE_SUBSCRIBERS_COUNT = 2;
  const UNCONFIRMED_SUBSCRIBERS_COUNT = 3;
  const UNSUBSCRIBED_SUBSCRIBERS_COUNT = 6;
  const SUBSCRIBER_LIST_NAME = 'Single Subscriber List';
  const SUBSCRIBERS_LIST_NAME = 'Various Subscribers';

  public function verifySubscribersShortcodeWithSegments(\AcceptanceTester $i) {
    $i->wantTo('Create page with shortcode of one subscriber and segment');
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName(self::SUBSCRIBER_LIST_NAME)->create();
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->create();
    $pageTitle = 'Subscribers Shortcode Page';
    $pageText = 'Your subscriber count is';
    $pageContent = "$pageText [mailpoet_subscribers_count segments=\"{$segment->getId()}\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title='$pageTitle'", "--post_content='$pageContent'"]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle);
    $i->clickItemRowActionByItemName($pageTitle, 'View');
    $i->waitForText($pageTitle);
    $i->waitForText("$pageText 1");
  }

  public function verifySubscribersShortcodeAllCounts(\AcceptanceTester $i) {
    $i->wantTo('Create page with shortcode of all subscribers but different statuses');
    $pageTitle = 'Subscribers Shortcode Page';
    $pageText = 'Your subscriber count is';
    $pageContent = "$pageText [mailpoet_subscribers_count]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title='$pageTitle'", "--post_content='$pageContent'"]);
    $this->prepareSubscribersData();
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle);
    $i->clickItemRowActionByItemName($pageTitle, 'View');
    $i->waitForText($pageTitle);
    $i->waitForText("$pageText 5");
  }

  private function prepareSubscribersData() {
    $segment = (new Segment())->withName(self::SUBSCRIBERS_LIST_NAME)->create();
    for ($i = 0; $i < self::ACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withSegments([$segment])->create();
    }
    for ($i = 0; $i < self::INACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('inactive')->withSegments([$segment])->create();
    }
    for ($i = 0; $i < self::UNCONFIRMED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('unconfirmed')->withSegments([$segment])->create();
    }
    for ($i = 0; $i < self::UNSUBSCRIBED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('unsubscribed')->withSegments([$segment])->create();
    }
  }
}
