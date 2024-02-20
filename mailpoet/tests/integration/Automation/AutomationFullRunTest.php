<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation;

use ActionScheduler_QueueRunner;
use ActionScheduler_Store;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\Core\Actions\IfElseAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\User;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class AutomationFullRunTest extends \MailPoetTest {
  private AutomationStorage $automationStorage;
  private AutomationRunStorage $automationRunStorage;

  public function _before(): void {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->cleanup();
  }

  public function testAutomationWithIfElseStepAndInTheLastParam(): void {
    $automation = (new \MailPoet\Test\DataFactories\Automation())
      ->withSteps([
        new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('trigger')]),
        new Step('trigger', Step::TYPE_TRIGGER, SomeoneSubscribesTrigger::KEY, [], [new NextStep('if-else')]),
        new Step(
          'if-else',
          Step::TYPE_ACTION,
          IfElseAction::KEY,
          [],
          [new NextStep('yes'), new NextStep('no')],
          new Filters(
            'and',
            [
              new FilterGroup(
                'group',
                'and',
                [
                  // customers with at least one review in the last 7 days
                  new Filter(
                    'filter',
                    Field::TYPE_INTEGER,
                    'woocommerce:customer:review-count',
                    'greater-than',
                    [
                      'value' => 0,
                      'params' => ['in_the_last' => ['number' => 7, 'unit' => 'days']],
                    ]
                  ),
                ]
              ),
            ]
          )
        ),
        new Step('yes', Step::TYPE_ACTION, DelayAction::KEY, ['delay' => 1, 'delay_type' => 'MINUTES'], []),
        new Step('no', Step::TYPE_ACTION, DelayAction::KEY, ['delay' => 3, 'delay_type' => 'HOURS'], []),
      ])
      ->withStatus(Automation::STATUS_ACTIVE)
      ->create();

    // customers
    $yesCustomer = (new User)->createUser('Yes', 'customer', 'yes@test.com');
    $noCustomer = (new User)->createUser('No', 'customer', 'no@test.com');

    // subscribers & segments
    $segment = (new Segment())->withName('Test segment')->create();
    $yesSubscriber = (new Subscriber())->withWpUserId($yesCustomer->ID)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$segment])->create();
    $noSubscriber = (new Subscriber())->withWpUserId($noCustomer->ID)->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$segment])->create();

    // reviews
    $this->tester->createWooProductReview($yesCustomer->ID, '', 1, 5, Carbon::now());
    $this->tester->createWooProductReview($noCustomer->ID, '', 2, 1, Carbon::now()->subMonth());

    // run triggers
    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $yesSubscriberSegment = $yesSubscriber->getSubscriberSegments()[0];
    $noSubscriberSegment = $noSubscriber->getSubscriberSegments()[0];
    $this->assertNotNull($yesSubscriberSegment);
    $this->assertNotNull($noSubscriberSegment);
    $trigger->handleSubscription($yesSubscriberSegment);
    $trigger->handleSubscription($noSubscriberSegment);
    $this->assertCount(2, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    // if-else steps should be scheduled
    $actions = $this->getScheduledActions();
    $this->assertCount(2, $actions);
    [$action1, $action2] = $actions;
    $this->assertSame('mailpoet/automation/step', $action1->get_hook());
    $this->assertSame('if-else', $action1->get_args()[0]['step_id']);
    $this->assertSame('mailpoet/automation/step', $action2->get_hook());
    $this->assertSame('if-else', $action2->get_args()[0]['step_id']);

    // execute if-else steps
    $runner = new ActionScheduler_QueueRunner();
    $runner->run();

    // check yes/no branches
    $actions = $this->getScheduledActions();
    $this->assertCount(2, $actions);
    [$action1, $action2] = $actions;

    $this->assertSame('mailpoet/automation/step', $action1->get_hook());
    $this->assertSame('yes', $action1->get_args()[0]['step_id']);
    $run1 = $this->automationRunStorage->getAutomationRun($action1->get_args()[0]['automation_run_id']);
    $subscriberId1 = $run1 ? $run1->getSubjects('mailpoet:subscriber')[0]->getArgs()['subscriber_id'] : null;
    $this->assertSame($yesSubscriber->getId(), $subscriberId1);

    $this->assertSame('mailpoet/automation/step', $action2->get_hook());
    $this->assertSame('no', $action2->get_args()[0]['step_id']);
    $run2 = $this->automationRunStorage->getAutomationRun($action2->get_args()[0]['automation_run_id']);
    $subscriberId2 = $run2 ? $run2->getSubjects('mailpoet:subscriber')[0]->getArgs()['subscriber_id'] : null;
    $this->assertSame($noSubscriber->getId(), $subscriberId2);
  }

  public function _after(): void {
    parent::_after();
    $this->cleanup();
  }

  private function getScheduledActions(): array {
    return array_values(
      as_get_scheduled_actions([
        'group' => 'mailpoet-automation',
        'status' => [ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING],
      ])
    );
  }

  private function cleanup(): void {
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();

    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
  }
}
