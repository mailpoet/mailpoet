<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberCountShortcodeCest {
  public function createSubscriberCountPageWithShortcode(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP subscriber shortcode');
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName('SubscriberCount')->create();
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->create();
    $pageTitle = 'OurSubscribers';
    $pageText = 'Your subscriber count is';
    $pageContent = "$pageText [mailpoet_subscribers_count segments=\"$segment->id\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle);
    $i->clickItemRowActionByItemName($pageTitle, 'View');
    $i->waitForText($pageTitle);
    $i->waitForText("$pageText 1");
  }
}
