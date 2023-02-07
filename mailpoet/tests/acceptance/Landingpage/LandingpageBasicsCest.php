<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
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
    $i->wantTo('Close the poll about MailPoet deactivation.');
    $i->pressKey('body', WebDriverKeys::ESCAPE);
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

    (new Features())->withFeatureEnabled(FeaturesController::LANDINGPAGE_AB_TEST_DEBUGGER); // enable ab test debugger

    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Better email — without leaving WordPress');

    $this->selectAbTestVariant($i, 'landing_page_cta_display_variant_begin_setup');

    $i->click('Begin setup');

    $i->waitForText('Start by configuring your sender information');
  }

  public function abTestButtonWorks(\AcceptanceTester $i) {
    $i->wantTo('Check landingpage AB test button works');
    $i->login();

    // show welcome wizard & landing page
    $settings = new Settings();
    $settings->withWelcomeWizard();

    (new Features())->withFeatureEnabled(FeaturesController::LANDINGPAGE_AB_TEST_DEBUGGER);

    $i->amOnMailpoetPage('Emails');

    $this->selectAbTestVariant($i, 'landing_page_cta_display_variant_begin_setup');

    $i->see('Begin setup');

    $this->selectAbTestVariant($i, 'landing_page_cta_display_variant_get_started_for_free');

    $i->see('Get started for free');
  }

  private function selectAbTestVariant(\AcceptanceTester $i, $testVariant) {
    $i->canSeeElementInDOM('#pushtell-debugger');
    $i->click('.pushtell-container.pushtell-handle'); // open debug panel
    $i->see($testVariant);
    $i->selectOption(".pushtell-experiment input[value=$testVariant]", $testVariant);
    $i->click('.pushtell-close'); // close debug panel

  }
}
