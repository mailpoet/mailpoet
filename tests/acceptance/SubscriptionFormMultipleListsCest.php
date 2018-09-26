<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;

require_once __DIR__ . '/../DataFactories/Form.php';

class SubscriptionFormMultipleListsCest {

  function editForm(\AcceptanceTester $I) {

    //Create form
    $form_name = 'Multiple List Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->create();

    $I->wantTo('Subscribe to multiple lists');
    //Create multiple lists
    $I->login();
    $I->amOnMailPoetPage('Lists');
    $I->click(['css'=> '.page-title-action']);
    $I->waitForText('Description', 10);
    $I->fillField(['name' => 'name'], "Cooking");
    $I->click('Save');
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('Cooking', 20);
    $I->click(['css'=> '.page-title-action']);
    $I->waitForText('Description', 10);
    $I->fillField(['name' => 'name'], "Camping");
    $I->click('Save');
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('Camping', 20);
    //edit form to include multiple lists
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name, 10);
    $I->clickItemRowActionByItemName($form_name, 'Edit');
    $title_element = '[data-automation-id="mailpoet_form_name_input"]';
    $I->waitForElement($title_element, 10);
    $I->seeInCurrentUrl('mailpoet-form-editor');
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'My First List');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->fillField($search_field_element, 'Cooking');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->fillField($search_field_element, 'Camping');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('[data-automation-id="save_form"]');
    //assertions
    $I->waitForText('Saved!', 10);

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