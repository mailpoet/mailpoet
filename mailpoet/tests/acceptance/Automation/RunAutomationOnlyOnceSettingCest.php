<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Test\DataFactories;

/**
 * This test contains active AutomateWoo plugin
 * in order to potentially catch issue with
 * blank page when managing automation with
 * the plugin AutomateWoo active.
 */
class RunAutomationOnlyOnceSettingCest {

  /** @var DataFactories\Settings */
  private $settingsFactory;

  /** @var ContainerWrapper */
  private $container;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  /** @var Automation */
  private $automation;

  private $segment;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $i->activateAutomateWoo();
    $this->container = ContainerWrapper::getInstance();
    $this->settingsFactory = new DataFactories\Settings();
    $this->settingsFactory->withCronTriggerMethod('Action Scheduler');
    $this->automationStorage = $this->container->get(AutomationStorage::class);
    $this->automationRunStorage = $this->container->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->container->get(AutomationRunLogStorage::class);


    $this->automation = (new DataFactories\Automation())
      ->withName('runAutomationOnlyOnce Automation')
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->withMeta('mailpoet:run-once-per-subscriber', false)
      ->withStatusActive()
      ->create();
    $this->segment = (new DataFactories\Segment())->withName('runAutomationOnlyOnce-segment')->create();
  }

  public function runAutomationOnlyOnce(\AcceptanceTester $i) {

    $subscriberEmail = 'run-automation-only-once-test@mailpoet.com';
    $i->wantTo('Ensure that a subscriber enters an automation only once when the setting is set');
    $i->login();
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Edit');
    $i->dontSee('Entered 1');
    $i->see('Entered 0'); //Actually I see "0 Entered", but this CSS switch is not caught by the test
    $i->click($this->automation->getName());
    $i->waitForText('Automation settings');
    $i->waitForText('Run this automation only once per subscriber.');
    $i->click('.mailpoet-automation-run-only-once label');

    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', $this->segment->getName());
    $i->click('Update');
    $i->waitForText('The automation has been saved.');

    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/new');
    $i->fillField('#field_email', $subscriberEmail);
    $i->fillField('#field_first_name', 'automation-tester-firstname');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText($this->automation->getName());
    $i->see('Entered 1'); //Actually I see "1 Entered", but this CSS switch is not caught by the test

    $i->amOnMailpoetPage('subscribers');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->click($subscriberEmail);
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->click('Remove item'); // Removes the newsletter list from the subscriber
    $i->click('Save');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->click($subscriberEmail);
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText($this->automation->getName());
    // No new run has been created.
    $i->see('Entered 1');
    $i->dontSee('Entered 2');
  }

  public function runAutomationMultipleTimes(\AcceptanceTester $i) {

    $this->automation = (new DataFactories\Automation())
      ->withName('runAutomationOnlyOnce Automation')
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->withMeta('run_only_once', false)
      ->withStatusActive()
      ->create();

    $subscriberEmail = 'run-automation-only-once-test@mailpoet.com';
    $i->wantTo('Ensure that a subscriber enters an automation only once when the setting is set');
    $i->login();
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Edit');
    $i->dontSee('Entered 1');
    $i->dontSee('Entered 2');
    $i->see('Entered 0'); //Actually I see "0 Entered", but this CSS switch is not caught by the test
    $i->click($this->automation->getName());
    $i->waitForText('Run this automation only once per subscriber.');
    $i->click('.mailpoet-automation-run-only-once'); //yes
    $i->click('.mailpoet-automation-run-only-once'); //no

    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', $this->segment->getName());
    $i->click('Update');
    $i->waitForText('The automation has been saved.');

    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/new');
    $i->fillField('#field_email', $subscriberEmail);
    $i->fillField('#field_first_name', 'automation-tester-firstname');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText($this->automation->getName());
    $i->see('Entered 1'); //Actually I see "1 Entered", but this CSS switch is not caught by the test
    $i->dontSee('Entered 2');

    $i->amOnMailpoetPage('subscribers');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->click($subscriberEmail);
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->click('Remove item'); // Removes the newsletter list from the subscriber
    $i->click('Save');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->click($subscriberEmail);
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText($this->automation->getName());

    // A new run has been created.
    $i->see('Entered 2');
    $i->dontSee('Entered 1');
  }

  public function _after(\AcceptanceTester $i) {
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }
}
