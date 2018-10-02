<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

require_once __DIR__ . '/../DataFactories/Form.php';
require_once __DIR__ . '/../DataFactories/Segment.php';

class SubscriptionFormMultipleListsCest {

  function editForm(\AcceptanceTester $I) {

    $segment_factory1 = new Segment();
    $segment_factory1->withName('Cooking');
    $segment1 = $segment_factory1->create();
    $segment_factory2 = new Segment();
    $segment_factory2->withName('Camping');
    $segment2 = $segment_factory2->create();

    //Create form
    $form_name = 'Multiple List Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->withSegments([$segment1, $segment2])->create();

    $I->wantTo('Subscribe to multiple lists');

    //add to sidebar
    $I->cli('widget reset sidebar-1 --allow-root');
    $I->cli('widget add mailpoet_form sidebar-1 3 --form=' . $form->id . ' --title="Subscribe to Multiple Lists" --allow-root');
    //subscribe to lists
    $subscriber_email = 'unicornmagic@example.com';
    $I->amOnPage('/');
    $I->fillField('[data-automation-id=\'form_email\']', $subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 20, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
    //confirm subscribed
    $I->amOnUrl('http://mailhog:8025');
    $I->waitForText('Confirm your subscription to', 20);
    $I->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->switchToIframe('preview-html');
    $I->waitForText('Cooking');
    $I->click('Click here to confirm your subscription');
    $I->switchToNextTab();
    $I->see('You have subscribed');
    $I->seeNoJSErrors();
    $I->amOnUrl('http://wordpress');
    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->waitForText($subscriber_email);
    $I->see('Subscribed', Locator::contains('tr', $subscriber_email));
  }
}
