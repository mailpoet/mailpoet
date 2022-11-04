<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStatisticsStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class SomeoneSubscribesWorkflowTriggeredByCheckoutCest
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

  private $product;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
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

    $this->product = (new WooCommerceProduct($i))->create();
  }

  public function workflowTriggeredByCheckout(\AcceptanceTester $i) {
    $i->wantTo("Activate a trigger by going through the Woocommerce checkout.");

    $this->settingsFactory->withConfirmationEmailDisabled(); // Just so we do not have to check our mailbox first.
    $this->createWorkflow();

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

  private function createWorkflow() : Workflow {
    $someoneSubscribesTrigger = $this->container->get(SomeoneSubscribesTrigger::class);
    $delayStep = $this->container->get(DelayAction::class);
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('t')]),
      't' => new Step('t', Step::TYPE_TRIGGER, $someoneSubscribesTrigger->getKey(), ['segment_ids' => []], [new NextStep('a1')]),
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
    if (! $storedWorkflow) {
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
