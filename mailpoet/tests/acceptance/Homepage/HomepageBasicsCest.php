<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class HomepageBasicsCest {
  public function homepageRenders(\AcceptanceTester $i) {
    $i->wantTo('Check homepage renders and is present in menu');
    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->waitForElement('.mailpoet-top-bar');
    $i->see('Home', '#adminmenu');
  }

  public function homepageRendersMailerError(\AcceptanceTester $i) {
    $i->wantTo('Check homepage can render Mailer error');
    (new Settings())->withSendingError('Sending is broken!');
    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->waitForElement('.mailpoet_notice');
    $i->waitForText('Sending is broken!');
    $i->waitForElementClickable('.mailpoet_notice .button');
    $i->click('Resume sending');
    $i->waitForText('Sending has been resumed');
    $i->dontSee('Sending is broken!');
  }

  public function homepageSectionsRender(\AcceptanceTester $i) {
    $subscriberFactory = new Subscriber();

    $i->wantTo('Check homepage renders all sections');
    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->wantTo('Check homepage renders task list');
    $i->waitForText('Welcome to MailPoet');
    $i->see('Begin by completing your setup');
    $i->see('Sender information added');
    $i->see('Connect MailPoet Sending Service');
    $i->wantTo('Hide task list');
    $i->click('button', '.mailpoet-task-list__heading');
    $i->waitForText('Hide setup list', 5, '.components-popover__content');
    $i->click('Hide setup list', '.components-popover__content');
    $i->dontSee('Begin by completing your setup');

    $i->wantTo('Check homepage renders product discovery section');
    $i->see('Start engaging with your customers');
    $i->see('Set up a welcome campaign');
    $i->see('Add a subscription form');
    $i->see('Send your first newsletter');
    $i->wantTo('Hide product discovery list');
    $productDiscoveryHeadingContext = '.mailpoet-homepage-product-discovery .mailpoet-homepage-section__heading';
    $i->click('button', $productDiscoveryHeadingContext);
    $i->waitForText('Hide setup list', 5, '.components-popover__content');
    $i->click('Hide setup list', '.components-popover__content');
    $i->dontSee('Start engaging with your customers');

    $i->wantTo('Check homepage subscribers stats section');
    $subscribersSection = '.mailpoet-subscribers-stats';
    $i->see('Subscribers', $subscribersSection);
    $i->see('Changes in the last 30 days', $subscribersSection);
    $i->see('Changes to your audience will appear here.', $subscribersSection);
    $i->wantTo('Check homepage subscribers stats section after adding a subscriber');
    $segment = (new Segment())->withName('Hello segment')->create();
    $subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $i->clearTransientCache();
    $i->reloadPage();
    $i->waitForText('Subscribers', 10, $subscribersSection);
    $i->see('New 1', $subscribersSection);
    $i->see('Unsubscribed 0', $subscribersSection);
    $i->see('List name', $subscribersSection);
    $i->see('Hello segment', $subscribersSection);

    $i->wantTo('Check homepage resources section');
    $resourcesSection = '.mailpoet-homepage-resources';
    $i->see('Learn more about email marketing', $resourcesSection);
    $i->see('Create an Email: Types of Campaigns', $resourcesSection);
    $i->see('Create a Subscription form', $resourcesSection);
    $i->see('Page 1 of 3', $resourcesSection);
    $i->click('a', '.mailpoet-homepage-resources__pagination');
    $i->waitForText('Page 2 of 3', 10, $resourcesSection);

    // The upsell section should be visible when task list and product discovery are closed
    // Another condition is 600 subscribers
    $subscriberFactory->createBatch(600, SubscriberEntity::STATUS_SUBSCRIBED);
    $i->reloadPage();
    $i->scrollToTop();
    $i->wantTo('Check homepage renders upsell section');
    $i->see('Accelerate your growth with our Business plan');
    $i->see('Detailed analytics');
    $i->see('Advanced subscriber segmentation');
    $i->see('Email marketing automations');
    $i->see('Priority support');
    $i->see('Upgrade plan');
    $i->click('.components-button', '.mailpoet-homepage-section__heading-after');
    $i->dontSee('Accelerate your growth with our Business plan');
  }
}
