<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class HomepageBasicsCest {
  public function _before() {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_HOMEPAGE);
  }

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
    // this big count of subscribers is needed for the upsell component
    $subscriberFactory = new Subscriber();
    $subscriberFactory->createBatch(600, SubscriberEntity::STATUS_SUBSCRIBED);

    $i->wantTo('Check homepage renders all sections');
    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->wantTo('Check homepage renders task list');
    $i->waitForText('Welcome to MailPoet');
    $i->see('Begin by completing your setup');
    $i->see('Sender information added');
    $i->see('Connect MailPoet sending service');
    $i->wantTo('Hide task list');
    $i->click('button', '.mailpoet-task-list__heading');
    $i->waitForText('Hide setup list', 5, '.mailpoet-task-list__heading .components-popover__content');
    $i->click('.components-popover__content button', '.mailpoet-task-list__heading');
    $i->dontSee('Begin by completing your setup');

    $i->wantTo('Check homepage renders product discovery section');
    $i->see('Start engaging with your customers');
    $i->see('Set up a welcome campaign');
    $i->see('Add a subscription form');
    $i->see('Send your first newsletter');
    $i->wantTo('Hide product discovery list');
    $productDiscoveryHeadingContext = '.mailpoet-homepage-product-discovery .mailpoet-homepage-section__heading';
    $i->click('button', $productDiscoveryHeadingContext);
    $i->waitForText('Hide setup list', 5, '.mailpoet-homepage-product-discovery .components-popover__content');
    $i->click('.components-popover__content button', $productDiscoveryHeadingContext);
    $i->dontSee('Start engaging with your customers');

    // The upsell section should be visible when task list and product discovery are closed
    // Another condition is 600 subscribers
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
