<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class LandingpageBasicsCest {
  public function landingpageRenders(\AcceptanceTester $i) {
    $i->wantTo('Check landing page renders');
    $i->login();

    // show welcome wizard & landing page
    $settings = new Settings();
    $settings->withWelcomeWizard();

    $i->amOnMailpoetPage('Emails');
    // should redirect to landing page
    $i->waitForText('Better email — without leaving WordPress');
  }

  public function landingpageRendersAfterActivation(\AcceptanceTester $i) {
    $i->wantTo('Check landingpage renders after plugin activation for new users');
    $i->login();

    $i->amOnPage('/wp-admin/plugins.php');
    $i->waitForText('Create and send newsletters, post notifications and welcome emails from your WordPress.');

    // show welcome wizard & landing page
    $settings = new Settings();
    $settings->withWelcomeWizard();

    $i->click('#deactivate-mailpoet');
    $i->waitForText('Plugin deactivated.');
    $i->click('#activate-mailpoet');
    $i->waitForText('Better email — without leaving WordPress');
  }

  public function landingpageRedirectsToWelcomeWizard(\AcceptanceTester $i) {
    $i->wantTo('Check landingpage redirect to welcome wizard when button is clicked');
    $i->login();

    // show welcome wizard & landing page
    $settings = new Settings();
    $settings->withWelcomeWizard();

    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Better email — without leaving WordPress');
    $i->click('Begin setup');

    $i->waitForText('Start by configuring your sender information');
  }
}
