<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group frontend
 */
class SubscriberCountShortcodeCest {

  const ACTIVE_SUBSCRIBERS_COUNT = 5;
  const INACTIVE_SUBSCRIBERS_COUNT = 2;
  const UNCONFIRMED_SUBSCRIBERS_COUNT = 3;
  const UNSUBSCRIBED_SUBSCRIBERS_COUNT = 6;
  const BOUNCED_SUBSCRIBERS_COUNT = 2;
  const SUBSCRIBER_LIST_NAME = 'Single Subscriber List';
  const SUBSCRIBERS_LIST_NAME_ONE = 'Various Subscribers List One';
  const SUBSCRIBERS_LIST_NAME_TWO = 'Various Subscribers List Two';
  const PAGE_TITLE = 'Subscribers Shortcode Page';
  const PAGE_TEXT = 'Your subscriber count is';

  public function verifySubscribersShortcodeWithSegments(\AcceptanceTester $i) {
    $i->wantTo('Create page with shortcode of one subscriber and segment');

    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName(self::SUBSCRIBER_LIST_NAME)->create();
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->create();
    $pageContent = self::PAGE_TEXT . " [mailpoet_subscribers_count segments={$segment->getId()}]";
    $postUrl = $i->createPost(self::PAGE_TITLE, $pageContent);

    $i->login();

    $i->amOnUrl($postUrl);
    $i->waitForText(self::PAGE_TITLE);
    $i->waitForText(self::PAGE_TEXT . " 1");
  }

  public function verifySubscribersShortcodeAllCounts(\AcceptanceTester $i) {
    $i->wantTo('Create page with shortcode of all subscribers but different statuses');

    $segmentFactory = new Segment();
    $segment1 = $segmentFactory->withName(self::SUBSCRIBERS_LIST_NAME_ONE)->create();
    $segment2 = $segmentFactory->withName(self::SUBSCRIBERS_LIST_NAME_TWO)->create();
    $pageContent = self::PAGE_TEXT . " [mailpoet_subscribers_count segments={$segment1->getId()},{$segment2->getId()}]";
    $postUrl = $i->createPost(self::PAGE_TITLE, $pageContent);

    $this->prepareSubscribersData($segment1, $segment2);

    $i->login();

    $i->amOnUrl($postUrl);
    $i->waitForText(self::PAGE_TITLE);
    $i->waitForText(self::PAGE_TEXT . " 10");

    $i->wantTo('Remove some subscribers and verify the shortcode rendering once again');

    $i->amOnMailpoetPage('Subscribers');
    $i->waitForElement('[data-automation-id="listing_filter_segment"]');
    $i->selectOption('[data-automation-id="listing_filter_segment"]', self::SUBSCRIBERS_LIST_NAME_TWO);
    $i->waitForElementVisible('[data-automation-id="listing_filter_segment"]');
    $i->click('[data-automation-id="select_all"]');
    $i->click('[data-automation-id="action-trash"]');
    $i->waitForListingItemsToLoad();
    $i->waitForNoticeAndClose('11 subscribers were moved to the trash.');
    $i->waitForText('No items found.');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForListingItemsToLoad();
    $i->waitForElementVisible('[data-automation-id="empty_trash"]');
    $i->click('[data-automation-id="empty_trash"]');
    $i->waitForNoticeAndClose('11 subscribers were permanently deleted.');
    $i->amOnUrl($postUrl);
    $i->waitForText(self::PAGE_TITLE);
    $i->waitForText(self::PAGE_TEXT . " 5");
  }

  private function prepareSubscribersData(SegmentEntity $segment1, SegmentEntity $segment2) {
    for ($i = 0; $i < self::ACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withSegments([$segment1])->create();
    }
    for ($i = 0; $i < self::INACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('inactive')->withSegments([$segment1])->create();
    }
    for ($i = 0; $i < self::UNCONFIRMED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('unconfirmed')->withSegments([$segment1])->create();
    }
    for ($i = 0; $i < self::UNSUBSCRIBED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('unsubscribed')->withSegments([$segment1])->create();
    }
    for ($i = 0; $i < self::BOUNCED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('bounced')->withSegments([$segment1])->create();
    }
    for ($i = 0; $i < self::ACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withSegments([$segment2])->create();
    }
    for ($i = 0; $i < self::UNSUBSCRIBED_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('unsubscribed')->withSegments([$segment2])->create();
    }
  }
}
