<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;

class ViewSegmentSubscribersCest {

  public function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

  public function viewUserRoleSegmentSubscribers(\AcceptanceTester $i) {
    $i->wantTo('View WP user role segment subscribers');

    $wpAdminEmail = 'test-admin@example.com';
    $wpEditorEmail = 'test-editor@example.com';
    $wpAuthorEmail = 'test-author@example.com';
    $segmentTitle = 'User Role Segment Test';

    $this->createUser('Test Admin', 'admin', $wpAdminEmail);
    $this->createUser('Test Editor', 'editor', $wpEditorEmail);
    $this->createUser('Test Author', 'author', $wpAuthorEmail);

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Editor')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->seeInCurrentUrl('segment=' . $segment->id);
    $i->waitForText($wpEditorEmail, 20);
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->dontSee($wpAdminEmail);
    $i->dontSee($wpAuthorEmail);
    $i->seeNoJSErrors();
  }

  private function createUser($name, $role, $email) {
    $userId = wp_create_user($name, "$name-password", $email);
    $user = get_user_by('ID', $userId);
    foreach ($user->roles as $defaultRole) {
      $user->remove_role($defaultRole);
    }
    $user->add_role($role);
    return $user;
  }
}
