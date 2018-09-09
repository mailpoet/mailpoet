<?php
namespace MailPoet\Test\Acceptance;
class DeleteExistingNotificationCest {
  function deleteExistingNotification(\AcceptanceTester $I) {
    $I->wantTo('Delete a post notification email');
    $newsletter_title = 'Delete Notification Test ' . \MailPoet\Util\Security::generateRandomString();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');
    // step 1 - select notification type
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_notification\']');
	$I->waitForText ('Latest Post Notifications');
	$I->click('Next');
    // step 2 - select template
    $notification_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($notification_template);
    $I->see('Post Notifications', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($notification_template);
    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');
    // step 4 - Choose list and save
    $I->waitForText('Final Step: Last Details');
    $I->seeInCurrentUrl('mailpoet-newsletters#/send/');
	$I->seeInField('Subject',$newsletter_title);
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'WordPress Users');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('Save as draft and close');
    $I->waitForText($newsletter_title, 5, '[data-automation-id="listing_item_1"]');
	// step 5 - Trashenate this newsletter
    $I->moveMouseOver(['xpath' => '//*[text()="'.$newsletter_title.'"]//ancestor::tr']);
    $I->makeScreenshot('after_mouse_over');
    $I->click('Move to trash', ['xpath' => '//*[text()="'.$newsletter_title.'"]//ancestor::tr']);
  }
}