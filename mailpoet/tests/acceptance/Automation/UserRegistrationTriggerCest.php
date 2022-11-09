<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
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

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowRunLogStorage */
  private $workflowRunLogStorage;

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
    $this->workflowStorage = $this->container->get(WorkflowStorage::class);
    $this->workflowRunStorage = $this->container->get(WorkflowRunStorage::class);
    $this->workflowRunLogStorage = $this->container->get(WorkflowRunLogStorage::class);
  }

  public function workflowTriggeredByRegistrationWithoutConfirmationNeeded(\AcceptanceTester $i, $scenario) {
    $scenario->skip('Temporally skip this test as it is failing when testing with WP multisite');
    $i->wantTo("Activate a trigger by registering.");
    $this->settingsFactory
      ->withSubscribeOnRegisterEnabled()
      ->withConfirmationEmailDisabled();
    $this->createWorkflow();

    $i->login();

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');
    $i->logOut();

    $this->registerWith($i,'workflowtriggeredbyregistration', 'test@mailpoet.com');

    $i->login();
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->dontSee('Entered 0');
    $i->see('Entered 1'); //The visible text is 1 Entered, but in the DOM it's the other way around.
  }

  public function workflowTriggeredByRegistrationWitConfirmationNeeded(\AcceptanceTester $i, $scenario) {
    $scenario->skip('Temporally skip this test as it is failing when testing with WP multisite');
    $i->wantTo("Activate a trigger by registering.");
    $this->settingsFactory
      ->withSubscribeOnRegisterEnabled()
      ->withConfirmationEmailEnabled();
    $this->createWorkflow();

    $i->login();

    $i->amOnMailpoetPage('automation');
    $i->waitForText('Entered');
    $i->see('Entered 0'); //The visible text is 0 Entered, but in the DOM it's the other way around.
    $i->dontSee('Entered 1');
    $i->logOut();

    $this->registerWith($i,'workflowtriggeredbyregistration', 'test@mailpoet.com');

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

  private function createWorkflow(): Workflow {
    $someoneSubscribesTrigger = $this->container->get(UserRegistrationTrigger::class);
    $delayStep = $this->container->get(DelayAction::class);
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('t')]),
      't' => new Step('t', Step::TYPE_TRIGGER, $someoneSubscribesTrigger->getKey(), ['roles' => []], [new NextStep('a1')]),
      'a1' => new Step('a1', Step::TYPE_ACTION, $delayStep->getKey(), ['delay' => 1, 'delay_type' => 'HOURS'], []),
    ];
    $workflow = new Workflow(
      'test',
      $steps,
      new \WP_User(1)
    );
    $workflow->setStatus(Workflow::STATUS_ACTIVE);
    $id = $this->workflowStorage->createWorkflow($workflow);
    $storedWorkflow = $this->workflowStorage->getWorkflow($id);
    if (!$storedWorkflow) {
      throw new \Exception("Workflow not found.");
    }
    return $storedWorkflow;
  }

  public function _after() {
    $this->workflowStorage->truncate();
    $this->workflowRunStorage->truncate();
    $this->workflowRunLogStorage->truncate();
  }
}
