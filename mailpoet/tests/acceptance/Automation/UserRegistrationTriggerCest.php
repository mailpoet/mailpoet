<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Settings;

class UserRegistrationTriggerCest {

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

    $this->registerWith($i, 'automationtriggeredbyregistration', 'test@mailpoet.com');

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

    $this->registerWith($i, 'automationtriggeredbyregistration', 'test@mailpoet.com');

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

  private function createAutomation() {
    $userRegistrationTrigger = $this->container->get(UserRegistrationTrigger::class);
    (new AutomationFactory())
      ->withName('test')
      ->addStep(new Step('t', Step::TYPE_TRIGGER, $userRegistrationTrigger->getKey(), ['roles' => []], []))
      ->withDelayAction()
      ->withStatusActive()
      ->create();
  }

  public function _after() {
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }
}
