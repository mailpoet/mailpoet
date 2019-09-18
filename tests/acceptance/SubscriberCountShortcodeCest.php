<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberCountShortcodeCest {
  function createSubscriberCountPageWithShortcode(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP subscriber shortcode');
    $segment_factory = new Segment();
    $segment = $segment_factory->withName('SubscriberCount')->create();
    $subscriber_factory = new Subscriber();
    $subscriber_factory->withSegments([$segment])->create();
    $pageTitle = 'OurSubscribers';
    $pageText = 'Your subscriber count is';
    $pageContent = "$pageText [mailpoet_subscribers_count segments=\"$segment->id\"]";
    $I->cli(['post', 'create', '--allow-root', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle);
    $I->click($pageTitle);
    //see live page with shortcode output
    $I->click('View Page');
    $I->waitForText($pageTitle);
    $I->waitForText("$pageText 1");
  }
}
