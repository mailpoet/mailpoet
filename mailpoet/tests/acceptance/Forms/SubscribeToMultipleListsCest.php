<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Subscription\Captcha;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

class SubscribeToMultipleListsCest {
  public function subscribeToMultipleLists(\AcceptanceTester $i) {
    //Step one - create form with three lists
    $segmentFactory = new Segment();
    $seg1 = 'Cats';
    $seg2 = 'Dogs';
    $seg3 = 'Fish';
    $segment1 = $segmentFactory->withName($seg1)->create();
    $segment2 = $segmentFactory->withName($seg2)->create();
    $segment3 = $segmentFactory->withName($seg3)->create();
    $formName = 'Multiple Lists Form';
    $formFactory = new Form();
    $form = $formFactory->withName($formName)->withSegments([$segment1, $segment2, $segment3])->create();

    $settings = new Settings();
    $settings
      ->withConfirmationEmailEnabled()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailSubject('Subscribe to multiple test subject')
      ->withCaptchaType(Captcha::TYPE_DISABLED);

    $formFactory->withDefaultSuccessMessage();

    //Add this form to a widget
    $i->createFormAndSubscribe($form);
    //Subscribe via that form
    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', 'Subscribe to multiple test subject'));
    $i->switchToIframe('#preview-html');
    $i->click('I confirm my subscription!');
    $i->switchToNextTab();
    $i->see('You have subscribed');
    $i->seeNoJSErrors();
  }
}
