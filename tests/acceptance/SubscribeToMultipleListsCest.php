<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

class SubscribeToMultipleListsCest {

  public function subscribeToMultipleLists(\AcceptanceTester $I) {
    //Step one - create form with three lists
    $segment_factory = new Segment();
    $seg1 = 'Cats';
    $seg2 = 'Dogs';
    $seg3 = 'Fish';
    $segment1 = $segment_factory->withName($seg1)->create();
    $segment2 = $segment_factory->withName($seg2)->create();
    $segment3 = $segment_factory->withName($seg3)->create();
    $form_name = 'Multiple Lists Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->withSegments([$segment1, $segment2, $segment3])->create();

    $settings = new Settings();
    $settings
      ->withConfirmationEmailEnabled()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailSubject('Subscribe to multiple test subject');

    $form_factory->withDefaultSuccessMessage();

    //Add this form to a widget
    $I->createFormAndSubscribe($form);
    //Subscribe via that form
    $I->amOnMailboxAppPage();
    $I->click(Locator::contains('span.subject', 'Subscribe to multiple test subject'));
    $I->switchToIframe('preview-html');
    $I->click('I confirm my subscription!');
    $I->switchToNextTab();
    $I->see('You have subscribed');
    $I->waitForText($seg1);
    $I->waitForText($seg2);
    $I->waitForText($seg3);
    $I->seeNoJSErrors();
  }
}
