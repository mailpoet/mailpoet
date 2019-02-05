<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

require_once __DIR__ . '/../DataFactories/Segment.php';
require_once __DIR__ . '/../DataFactories/Subscriber.php';

class SubscriberCountShortcodeCest {
  function createSubscriberCountPageWithShortcode(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP archive shortcode, showing no sent newsletters');
    $segment_factory = new Segment();
    $segment = $segment_factory->withName('SubscriberCount')->create();
    $subscriber_factory = new Subscriber();
    $subscriber_factory->withSegments([$segment])->create();
    $pageTitle='OurSubscribers';
    $pageContent='[mailpoet_subscribers_count\ segments="' . $segment->id . '"]';
    $I->cli('post create --allow-root --post_type=page --post_title=' . $pageTitle . '  --post_content=' . $pageContent);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle);
    $I->click($pageTitle);
    $I->click('Publish');
    //see live page with shortcode output
    $I->click('View page');
    $I->waitForText($pageTitle);
    $I->waitForText('1');
  }
} 