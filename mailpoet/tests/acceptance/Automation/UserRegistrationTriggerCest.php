<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class UserRegistrationTriggerCest
{
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
    // @ToDo Remove once MVP is released.
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::AUTOMATION);
    $this->container = ContainerWrapper::getInstance();
    $migrator = $this->container->get(Migrator::class);
    $migrator->deleteSchema();
    $migrator->createSchema();

    $this->settingsFactory = new Settings();

    $this->settingsFactory->withCronTriggerMethod('Action Scheduler');
    $this->automationStorage = $this->container->get(AutomationStorage::class);
    $this->automationRunStorage = $this->container->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->container->get(AutomationRunLogStorage::class);
  }

  public function automationTriggeredByRegistrationWithoutConfirmationNeeded(\AcceptanceTester $i, $scenario) {
    $scenario->skip('Temporally skip this test as it is failing when testing with WP multisite');
    $i->wantTo("Activate a trigger by registering.");
    $this->settingsFactory
      ->withSubscribeOnRegisterEnabled()
      ->withConfirmationEmailDisabled();
    $this->createAutomation();

    $i->login();

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');
    $i->logOut();

    $this->registerWith($i,'automationtriggeredbyregistration', 'test@mailpoet.com');

    $i->login();
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->dontSee('Entered 0');
    $i->see('Entered 1'); //The visible text is 1 Entered, but in the DOM it's the other way around.
  }

  public function automationTriggeredByRegistrationWitConfirmationNeeded(\AcceptanceTester $i, $scenario) {
    $scenario->skip('Temporally skip this test as it is failing when testing with WP multisite');
    $i->wantTo("Activate a trigger by registering.");
    $this->settingsFactory
      ->withSubscribeOnRegisterEnabled()
      ->withConfirmationEmailEnabled();
    $this->createAutomation();

    $i->login();

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');
    $i->logOut();

    $this->registerWith($i,'automationtriggeredbyregistration', 'test@mailpoet.com');

    $i->login();

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');

    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $i->switchToIframe('#preview-html');
    $i->click('Click here to confirm your subscription.');

    $i->amonUrl('http://test.local');
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->dontSee('Entered 0');
    $i->see('Entered 1'); //The visible text is 1 Entered, but in the DOM it's the other way around.
  }

  private function registerWith(\AcceptanceTester $i, string $username, string $email, bool $signup = true) {
    $i->amOnPage("/wp-login.php?action=register");
    $i->wait(1);// this needs to be here, Username is not filled properly without this line
    $i->fillField('Username', $username);
    $i->fillField('Email', $email);
    if ($signup) {
      $i->click('#mailpoet_subscribe_on_register');
    }
    $i->click('input[type=submit]');

    // Waiting text in Try is for normal WP site, Catch is for multi-site as they differ in UI
    try {
      $i->waitForText('Registration complete.', 10);
    } catch (\Exception $e) {
      $i->waitForText($username . ' is your new username', 10);
    }
  }

  private function createAutomation(): Automation {
    $someoneSubscribesTrigger = $this->container->get(UserRegistrationTrigger::class);
    $delayStep = $this->container->get(DelayAction::class);
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('t')]),
      't' => new Step('t', Step::TYPE_TRIGGER, $someoneSubscribesTrigger->getKey(), ['roles' => []], [new NextStep('a1')]),
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
