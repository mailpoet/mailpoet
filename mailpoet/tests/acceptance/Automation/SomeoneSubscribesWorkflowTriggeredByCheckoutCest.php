<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class SomeoneSubscribesAutomationTriggeredByCheckoutCest {

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

  private $product;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->container = ContainerWrapper::getInstance();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withCronTriggerMethod('Action Scheduler');
    $this->automationStorage = $this->container->get(AutomationStorage::class);
    $this->automationRunStorage = $this->container->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->container->get(AutomationRunLogStorage::class);

    $this->product = (new WooCommerceProduct($i))->create();
  }

  public function automationTriggeredByCheckout(\AcceptanceTester $i) {
    $i->wantTo("Activate a trigger by going through the Woocommerce checkout.");

    $this->settingsFactory->withConfirmationEmailDisabled(); // Just so we do not have to check our mailbox first.
    $this->createAutomation();

    $i->login();
    $i->amOnMailpoetPage('settings');
    $i->click('[data-automation-id="woocommerce_settings_tab"]');
    $i->checkOption('#mailpoet_wc_checkout_optin');
    $i->selectOptionInReactSelect('Newsletter mailing list', '#mailpoet_wc_checkout_optin_segments');
    $i->click('Save settings');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');
    $i->logOut();

    $customerEmail = 'customer@mailpoet.com';
    $i->orderProductWithRegistration($this->product, $customerEmail, true);

    $i->login();
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 1'); //The visible text is 1 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 0');
  }

  private function createAutomation(): Automation {
    $someoneSubscribesTrigger = $this->container->get(SomeoneSubscribesTrigger::class);
    $delayStep = $this->container->get(DelayAction::class);
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('t')]),
      't' => new Step('t', Step::TYPE_TRIGGER, $someoneSubscribesTrigger->getKey(), ['segment_ids' => []], [new NextStep('a1')]),
      'a1' => new Step('a1', Step::TYPE_ACTION, $delayStep->getKey(), ['delay' => 1, 'delay_type' => 'HOURS'], []),
    ];
    $automation = new Automation(
      'test',
      $steps,
      new \WP_User(1)
    );
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $id = $this->automationStorage->createAutomation($automation);
    $storedAutomation = $this->automationStorage->getAutomation($id);
    if (!$storedAutomation) {
      throw new \Exception("Automation not found.");
    }
    return $storedAutomation;
  }

  public function _after() {
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }
}
