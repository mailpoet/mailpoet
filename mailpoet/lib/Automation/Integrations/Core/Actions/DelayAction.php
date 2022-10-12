<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\ValidationException;
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
      'delay' => Builder::integer()->required()->minimum(1),
      'delay_type' => Builder::string()->required()->pattern('^(DAYS|HOURS|WEEKS)$')->default('HOURS'),
    ]);
  }

  public function getSubjectKeys(): array {
    return [];
  }

  public function validate(StepValidationArgs $args): void {
    $seconds = $this->calculateSeconds($args->getStep());
    if ($seconds <= 0) {
      throw new ValidationException(__('A delay must have a positive value', 'mailpoet'));
    }
    if ($seconds > 2 * YEAR_IN_SECONDS) {
      throw new ValidationException(__("A delay can't be longer than two years", 'mailpoet'));
    }
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
