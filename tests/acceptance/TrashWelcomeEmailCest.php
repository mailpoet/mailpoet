<?php

namespace MailPoet\Test\Acceptance;

class TrashWelcomeEmailCest {
  function trashWelcomeEmail(\AcceptanceTester $I) {
    $I->wantTo('Trash existing welcome email');
    $newsletter_title = 'Welcome Unicorn Email ' . \MailPoet\Util\Security::generateRandomString();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');
        // select notification type
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_welcome\']');
        //select when to send and to whom
    $I->waitForText('Welcome Email');
    $I->seeInCurrentUrl('#/new/welcome');
    $I->selectOption ('select[name=event]', 'When a new WordPress user is added to your site...');
    $I->selectOption ('select[name=role]', 'Subscriber');
    $I->selectOption('select[name=afterTimeType]', 'immediately');
    $I->click('Next');
      //Select a pretty template
    $welcome_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($welcome_template);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($post_notification_template);
      //Add a title in the editor
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');
        //Avengers Assemble, but really, Activate
    $search_field_element = 'input.select2-search__field';
    $I->waitForElement($search_field_element);
    $I->seeInCurrentUrl('#/send');
    $I->click('Activate');
    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_title, $newsletter_listing_element);
        //now to test the trashening    
    $I->amOnMailpoetPage('Emails');
	//switch to welcome email tab
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
	//assertion: email exists in list
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_2"]');
	//display hover-only text for dumb robot
    //$I->executeJS("$('.row-actions').css({'display':'block'});");
	//to the trash with you
    $I->click('Move to trash');
	//navigate to trash page
    $I->amOnMailpoetPage('mailpoet-newsletters#/welcome/page[1]/sort_by[updated_at]/sort_order[desc]/group[trash]');
	//assertion: email exists in trash
    $I->waitForText ('Welcome email');
    
  }
}
