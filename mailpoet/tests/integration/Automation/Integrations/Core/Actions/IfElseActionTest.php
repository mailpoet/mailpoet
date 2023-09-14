<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Actions;

use ActionScheduler_Action;
use ActionScheduler_Store;
use MailPoet\Automation\Engine\Control\StepRunControllerFactory;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\Core\Actions\IfElseAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetTest;
use WP_User;

class IfElseActionTest extends MailPoetTest {
  /** @var IfElseAction */
  private $action;

  /** @var SubscriberSubject */
  private $subscriberSubject;

  public function _before(): void {
    $this->cleanup();
    $this->action = $this->diContainer->get(IfElseAction::class);
    $this->subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
  }

  public function testItDoesntRequireAnySubjects(): void {
    $this->assertEmpty($this->action->getSubjectKeys());
  }

  public function testItValidatesNextSteps(): void {
    $automation = new Automation('test-automation', [], new WP_User());
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', [], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($automation, $step, []));
    } catch (ValidationException $error) {
      $this->assertSame('If/Else action must have exactly two next steps.', $error->getErrors()['if_else_next_steps_count']);
    }
    $this->assertNotNull($error);
  }

  public function testItValidatesConditions(): void {
    $automation = new Automation('test-automation', [], new WP_User());
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', [], [new NextStep('n1'), new NextStep('n2')]);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($automation, $step, []));
    } catch (ValidationException $error) {
      $this->assertSame('If/Else action must have at least one condition set.', $error->getErrors()['if_else_conditions_count']);
    }
    $this->assertNotNull($error);
  }

  public function testItRunsThroughYesBranch(): void {
    $this->testItRuns('yes', SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItRunsThroughNoBranch(): void {
    $this->testItRuns('no', SubscriberEntity::STATUS_INACTIVE);
  }

  private function testItRuns(string $expectedBranchId, string $filterStatus): void {
    // filter by subscriber status
    $filterValue = ['value' => [$filterStatus]];
    $filter = new Filter('f', Field::TYPE_ENUM, 'mailpoet:subscriber:status', 'is-any-of', $filterValue);
    $filters = new Filters('and', [new FilterGroup('g', 'and', [$filter])]);

    // automation & step
    $nextSteps = [new NextStep('yes'), new NextStep('no')];
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', [], $nextSteps, $filters);
    $automation = new Automation('test-automation', [$step->getId() => $step], new WP_User());

    // subject
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subject = new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriber->getId()]);

    // run
    $run = new AutomationRun(1, 1, 'trigger-key', [$subject], 1);
    $args = new StepRunArgs($automation, $run, $step, [new SubjectEntry($this->subscriberSubject, $subject)], 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);
    $this->action->run($args, $controller);

    // check
    $actions = $this->getScheduledActions();
    $this->assertCount(1, $actions);
    $this->assertInstanceOf(ActionScheduler_Action::class, $actions[0]);
    $this->assertSame($expectedBranchId, $actions[0]->get_args()[0]['step_id'] ?? '');
  }

  private function getScheduledActions(): array {
    return array_values(
      as_get_scheduled_actions([
        'group' => 'mailpoet-automation',
        'status' => [ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING],
      ])
    );
  }

  public function _after() {
    $this->cleanup();
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
  }
}
