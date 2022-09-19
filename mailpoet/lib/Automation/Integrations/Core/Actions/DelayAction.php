<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

class DelayAction implements Action {
  /** @var ActionScheduler */
  private $actionScheduler;

  public function __construct(
    ActionScheduler $actionScheduler
  ) {
    $this->actionScheduler = $actionScheduler;
  }

  public function getKey(): string {
    return 'core:delay';
  }

  public function getName(): string {
    return __('Delay', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'delay' => Builder::integer()->minimum(1),
      'delay_type' => Builder::string()->default('HOURS'),
    ]);
  }

  public function getSubjectKeys(): array {
    return [];
  }

  public function run(StepRunArgs $args): void {
    $step = $args->getStep();
    $nextStep = $step->getNextSteps()[0] ?? null;
    $this->actionScheduler->schedule(time() + $this->calculateSeconds($step), Hooks::WORKFLOW_STEP, [
      [
        'workflow_run_id' => $args->getWorkflowRun()->getId(),
        'step_id' => $nextStep ? $nextStep->getId() : null,
      ],
    ]);

    // TODO: call a step complete ($id) hook instead?
  }

  public function isValid(array $subjects, Step $step, Workflow $workflow): bool {
    $seconds = $this->calculateSeconds($step);

    return $seconds > 0 && $seconds < 2 * YEAR_IN_SECONDS;
  }

  private function calculateSeconds(Step $step): int {
    $delay = (int)($step->getArgs()['delay'] ?? null);
    switch ($step->getArgs()['delay_type']) {
      case "HOURS":
        return $delay * HOUR_IN_SECONDS;
      case "DAYS":
        return $delay * DAY_IN_SECONDS;
      case "WEEKS":
        return $delay * WEEK_IN_SECONDS;
      default:
        return 0;
    }
  }
}
