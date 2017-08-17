<?php
/*
 * Test summary: Test Newsletters page works
 *
 * Test details:
 * - Open newsletters from the menu
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Open newsletters page');

$I->loginAsAdmin();
$I->seeInCurrentUrl('/wp-admin/');
// Go to Status
$I->amOnMailpoetPage('Emails');
$I->waitForElement('#newsletters_container', 3);
