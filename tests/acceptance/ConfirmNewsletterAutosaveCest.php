<?php
namespace MailPoet\Test\Acceptance;
class ConfirmNewsletterAutoSaveCest {
  function confirmNewsletterAutoSave(\AcceptanceTester $I){
    $I->wantTo('Confirm autosave works as advertised');
	
    $newsletter_title = 'Autosave Test ' . \MailPoet\Util\Security::generateRandomString();
    
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');
    
	// step 1 - select notification type
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_standard\']');
	
	// step 2 - select template
    $standard_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($standard_template);	
  
    // step 3 - Add subject, wait for Autosave
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
	$I->wait(20);
	$I->see('Autosaved');
  }
}