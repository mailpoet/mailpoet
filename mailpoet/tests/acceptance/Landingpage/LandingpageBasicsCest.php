<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class LandingpageBasicsCest {
  public function _before() {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_LANDINGPAGE);
  }

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

  public function homepageRendersAfterActivation(\AcceptanceTester $i) {
    $i->wantTo('Check landingpage renders after plugin activation for new users');
    $i->login();

    // show welcome wizard & landing page
    $settings = new Settings();
    $settings->withWelcomeWizard();


    $i->amOnPage('/wp-admin/plugins.php');
    $i->waitForText('Create and send newsletters, post notifications and welcome emails from your WordPress.');

    $i->click('#deactivate-mailpoet');
    $i->waitForText('Plugin deactivated.');
    $i->click('#activate-mailpoet');
    $i->waitForText('Better email — without leaving WordPress');
  }
}
