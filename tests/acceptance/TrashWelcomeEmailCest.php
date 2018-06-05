<?php
namespace MailPoet\Test\Acceptance;
class TrashWelcomeEmailCest {
  function listsListing(\AcceptanceTester $I) {
    $I->wantTo('Trash existing welcome email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
	$I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_2"]');
    $I->executeJS("$('.row-actions').css({'display':'block'});");
	$I->click('Move to trash');
	$I->amOnMailpoetPage('mailpoet-newsletters#/welcome/page[1]/sort_by[updated_at]/sort_order[desc]/group[trash]');
	$I->waitForText ('Welcome email');
    
  }
}