<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Settings;

class SomeoneSubscribesAutomationTriggeredBySubscriberEditCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var ContainerWrapper */
  private $container;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  public function _before(\AcceptanceTester $i) {
    $this->container = ContainerWrapper::getInstance();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withCronTriggerMethod('Action Scheduler');
    $this->automationStorage = $this->container->get(AutomationStorage::class);
    $this->automationRunStorage = $this->container->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->container->get(AutomationRunLogStorage::class);
  }

  public function automationTriggeredByCheckout(\AcceptanceTester $i) {
    $i->wantTo("Activate a trigger by editing a subscriber.");

    $this->settingsFactory->withConfirmationEmailDisabled(); // Just so we do not have to check our mailbox first.

    $someoneSubscribesTrigger = $this->container->get(SomeoneSubscribesTrigger::class);
    (new AutomationFactory())
      ->withName('test')
      ->withStep(new Step('t', Step::TYPE_TRIGGER, $someoneSubscribesTrigger->getKey(), ['segment_ids' => []], []))
      ->withDelayAction()
      ->withStatusActive()
      ->create();

    $i->login();

    $i->amOnMailpoetPage('subscribers');
    $i->click('[data-automation-id="add-new-subscribers-button"]');

    $subscriberEmail = 'someone@mailpoet.com';
    $i->fillField(['name' => 'email'], $subscriberEmail);
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Subscribed');
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');

    $this->amOnTheSubscriberEditPageFor($i, $subscriberEmail);
    $i->selectOptionInSelect2('Newsletter mailing list');
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 1');
    $i->dontSee('Entered 0');

    // Check that a second save action does not start the automation again.
    $this->amOnTheSubscriberEditPageFor($i, $subscriberEmail);
    $i->click('Save');

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 1');
    $i->dontSee('Entered 2');
  }

  private function amOnTheSubscriberEditPageFor(\AcceptanceTester $i, string $email) {
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForText($email);
    $i->click($email);
    $i->waitForElement('input[value="' . $email . '"]');
  }

  public function _after() {
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }
}
