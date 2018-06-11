<?php
namespace MailPoet\Test\Acceptance;
class TrashWelcomeEmailCest {
  function trashWelcomeEmail(\AcceptanceTester $I) {
    $I->wantTo('Trash existing welcome email');
    $I->login();
	//this is where I will make a welcome email until I can find a better way to do this
    $I->amOnMailpoetPage('Emails');
	//switch to welcome email tab
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
	//assertion: email exists in list
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_2"]');
	//display hover-only text for dumb robot
    $I->executeJS("$('.row-actions').css({'display':'block'});");
	//to the trash with you
    $I->click('Move to trash');
	//navigate to trash page
    $I->amOnMailpoetPage('mailpoet-newsletters#/welcome/page[1]/sort_by[updated_at]/sort_order[desc]/group[trash]');
	//assertion: email exists in trash
    $I->waitForText ('Welcome email');
    
  }
}
