<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;
use MailPoet\Test\DataFactories\WooCommerceMembership;

/**
 * @group woo
 */
class WooCommerceMembershipsSegmentCest {
  public function _before(\AcceptanceTester $i, $scenario) {
    if (!$i->canTestWithPlugin(\AcceptanceTester::WOO_COMMERCE_MEMBERSHIPS_PLUGIN)) {
      $scenario->skip('Can‘t test without woocommerce-memberships');
    }
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
    $i->activateWooCommerce();
  }

  public function _after(\AcceptanceTester $i) {
    $i->deactivateWooCommerce();
  }

  public function createSegmentForMembershipPlan(\AcceptanceTester $i) {
    $i->activateWooCommerceMemberships();
    $membershipFactory = new WooCommerceMembership($i);
    $plan1 = $membershipFactory->createPlan('Plan 1');
    $plan2 = $membershipFactory->createPlan('Plan 2');

    $userFactory = new User();
    $subscriber1 = $userFactory->createUser('Sub Scriber1', 'subscriber', 'subscriber1@example.com');
    $subscriber2 = $userFactory->createUser('Sub Scriber2', 'subscriber', 'subscriber2@example.com');
    $userFactory->createUser('Sub Scriber3', 'subscriber', 'subscriber3@example.com');
    $userFactory->createUser('Sub Scriber4', 'subscriber', 'subscriber4@example.com');

    $membershipFactory->createMember($subscriber1->ID, $plan1['id']);
    $membershipFactory->createMember($subscriber2->ID, $plan1['id']);
    $membershipFactory->createMember($subscriber2->ID, $plan2['id']);

    $segmentActionSelectElement = '[data-automation-id="select-segment-action"]';
    $operatorSelectElement = '[data-automation-id="select-operator"]';
    $segmentTitle = 'Woo Membership';
    $i->wantTo('Create dynamic segment for memberships');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'Desc ' . $segmentTitle);
    $i->selectOptionInReactSelect('is member of', $segmentActionSelectElement);
    $i->selectOptionInReactSelect('Plan 1', '[data-automation-id="select-segment-plans"]');
    $i->selectOptionInReactSelect('Plan 2', '[data-automation-id="select-segment-plans"]');
    $i->waitForText('This segment has');

    // Check for none of
    $i->selectOption($operatorSelectElement, 'none of');
    $i->waitForText('This segment has 3 subscribers.'); // subscriber3@example.com, subscriber4@example.com, and admin user
    // Check for all of
    $i->selectOption($operatorSelectElement, 'all of');
    $i->waitForText('This segment has 1 subscribers.'); // subscriber2@example.com
    // Check for any of
    $i->selectOption($operatorSelectElement, 'any of'); // subscriber2@example.com and subscriber1@example.com
    $i->waitForText('This segment has 2 subscribers.');

    $i->seeNoJSErrors();
    $i->click('Save');
    $i->wantTo('Check that segment contains correct subscribers');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->waitForText('subscriber1@example.com');
    $i->waitForText('subscriber2@example.com');

    $i->wantTo('Check that MailPoet plugin works when admin disables WooCommerce Memberships');
    $i->deactivateWooCommerceMemberships();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->canSee('Activate the WooCommerce Memberships plugin to see the number of subscribers and enable the editing of this segment.');

    $i->wantTo('Check that admin can‘t add new memberships segment when WooCommerce Memberships is not active');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement($segmentActionSelectElement);
    $i->fillField("$segmentActionSelectElement input", 'is member of');
    $i->canSee('No options', $segmentActionSelectElement);
  }
}
