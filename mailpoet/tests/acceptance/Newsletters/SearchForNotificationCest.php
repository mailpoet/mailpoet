<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class SearchForNotificationCest {
  public function searchForStandardNotification(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an existing and active notification');

    $newsletterTitle = 'Active Post Notification';
    $failureConditionNewsletter = 'Not Actually Real';
    $segmentName = 'Fancy List';

    // step 1 - Prepare newsletter data
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName($segmentName)->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->withSegments([$segment])
      ->withActiveStatus()
      ->create();

    // step 2 - Search
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForListingItemsToLoad();
    $i->searchFor($failureConditionNewsletter);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Filter by Active and search for the active post notification
    $i->click('[data-automation-id="filters_active"]');
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 4 - Filter by assigned list and make sure the notification is present
    $i->selectOption('[data-automation-id="listing_filter_segment"]', $segmentName);
    $i->waitForElement('[data-automation-id="listing_filter_segment"]');
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 5 - Filter by Inactive and search for the active post notification
    $i->click('[data-automation-id="filters_not_active"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
  }

  public function searchForStandardNotificationHistoryItem(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an active notification history item');

    $newsletterTitle = 'New Post Alert [newsletter:post_title]';
    $segmentName = 'New post alert list';
    $staticPostTitle = 'this is just another post';
    $dynamicPostTitle = 'Hello how do you do';

    (new Settings())
      ->withTrackingDisabled()
      ->withCronTriggerMethod('Action Scheduler');

    // step 1 - Prepare newsletter data
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName($segmentName)->create();

    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->create();

    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->withSegments([$segment])
      ->withActiveStatus()
      ->withImmediateSendingSettings()
      ->create();


    (new Newsletter())
      ->withSubject($staticPostTitle)
      ->withPostNotificationHistoryType()
      ->withParent($newsletter)
      ->create();

    $i->wait(1); //waiting 1 second so that post created time is after the newsletter

    // create a  WP post
    $i->cli(['post', 'create', "--post_title='$dynamicPostTitle'", '--post_content="Lorem Ipsum"', '--post_status=publish']);

    $i->triggerNewsletterScheduledTask($newsletter->getId());
    $i->triggerMailPoetActionScheduler();

    $i->wait(1);

    // step 2 - Search post-notification
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForListingItemsToLoad();
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]'); // confirm post-notification is created

    // step 3 - Search post-notification history item
    $selector = sprintf('[data-automation-id="history-%d"]', $newsletter->getId());
    $i->click($selector);
    $i->waitForListingItemsToLoad();

    $i->searchFor($staticPostTitle); // search by static newsletter subject
    $i->waitForText($staticPostTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    $i->searchFor($dynamicPostTitle); // search by rendered post title
    $i->waitForText($dynamicPostTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');
  }
}
