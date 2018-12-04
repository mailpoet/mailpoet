<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

require_once __DIR__ . '/../DataFactories/Form.php';
require_once __DIR__ . '/../DataFactories/Segment.php';

class SubscribeToMultipleListsCest {
  const CONFIRMATION_MESSAGE_TIMEOUT = 20;
  private $subscriber_email;
  function __construct() {
    $this->subscriber_email = 'multiple-test-form@example.com';
  }
  function subsrcibeToMultipleLists(\AcceptanceTester $I) {
    //Step one - create form with three lists
    $segment_factory = new Segment();
    $seg1= 'Cats';
    $seg2= 'Dogs';
    $seg3= 'Fish';
    $segment1 = $segment_factory->withName($seg1)->create();
    $segment2 = $segment_factory->withName($seg2)->create();
    $segment3 = $segment_factory->withName($seg3)->create();
    $form_name = 'Multiple Lists Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->withSegments([$segment1, $segment2, $segment3])->create();
    //Add this form to a widget
    $I->createFormAndSubscribe($form);
    //Subscribe via that form
    $I->amOnUrl(\AcceptanceTester::MAIL_URL);
    $I->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->switchToIframe('preview-html');
    $I->click('Click here to confirm your subscription');
    $I->switchToNextTab();
    $I->see('You have subscribed');
    $I->waitForText($seg1, 10);
    $I->waitForText($seg2, 10);
    $I->waitForText($seg3, 10);
    $I->seeNoJSErrors();
    //reset widget for other tests
    $I->cli('widget reset sidebar-1 --allow-root');
  }
}
