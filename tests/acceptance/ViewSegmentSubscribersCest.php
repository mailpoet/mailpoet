<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;

class ViewSegmentSubscribersCest {

  function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

  function viewUserRoleSegmentSubscribers(\AcceptanceTester $I) {
    $I->wantTo('View WP user role segment subscribers');

    $wp_admin_email = 'test-admin@example.com';
    $wp_editor_email = 'test-editor@example.com';
    $wp_author_email = 'test-author@example.com';
    $segment_title = 'User Role Segment Test';

    $this->createUser('Test Admin', 'admin', $wp_admin_email);
    $this->createUser('Test Editor', 'editor', $wp_editor_email);
    $this->createUser('Test Author', 'author', $wp_author_email);

    $segment_factory = new DynamicSegment();
    $segment = $segment_factory
      ->withName($segment_title)
      ->withUserRoleFilter('Editor')
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Segments');
    $listing_automation_selector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $I->waitForText($segment_title, 10, $listing_automation_selector);
    $I->clickItemRowActionByItemName($segment_title, 'View Subscribers');
    $I->seeInCurrentUrl('mailpoet-subscribers#');
    $I->seeInCurrentUrl('segment=' . $segment->id);
    $I->waitForText($wp_editor_email, 20);
    $I->see($segment_title, ['css' => 'select[name=segment]']);
    $I->dontSee($wp_admin_email);
    $I->dontSee($wp_author_email);
    $I->seeNoJSErrors();
  }

  private function createUser($name, $role, $email) {
    $user_id = wp_create_user($name, "$name-password", $email);
    $user = get_user_by('ID', $user_id);
    foreach ($user->roles as $default_role) {
      $user->remove_role($default_role);
    }
    $user->add_role($role);
    return $user;
  }
}
