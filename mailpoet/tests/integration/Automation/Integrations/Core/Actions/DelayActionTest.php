<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Control\StepRunControllerFactory;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;

class DelayActionTest extends \MailPoetTest {
  /**
   * @dataProvider dataForTestItCalculatesDelayTypesCorrectly
   */
  public function testItCalculatesDelayTypesCorrectly(int $delay, string $type, int $expectation) {
    $step = new Step(
      '1',
      'core:delay',
      'core:delay',
      [
        'delay' => $delay,
        'delay_type' => $type,
      ],
      [new NextStep('next-step')]
    );
    $automation = $this->createMock(Automation::class);
    $automationRun = $this->createMock(AutomationRun::class);
    $automationRun->expects($this->atLeastOnce())->method('getId')->willReturn(1);

    $actionScheduler = $this->createMock(ActionScheduler::class);
    $actionScheduler->expects($this->once())->method('schedule')->with(
      time() + $expectation,
      Hooks::AUTOMATION_STEP,
      [[
        'automation_run_id' => 1,
        'step_id' => 'next-step',
      ]]
    );
    $testee = new DelayAction($actionScheduler);
    $args = new StepRunArgs($automation, $automationRun, $step, [], 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);
    $testee->run($args, $controller);
  }

  public function dataForTestItCalculatesDelayTypesCorrectly(): array {
    return [
      '1_minute' => [
        1,
        "MINUTES",
        60,
      ],
      '3_minute' => [
        3,
        "MINUTES",
        3 * 60,
      ],
      '1_hour' => [
        1,
        "HOURS",
        3600,
      ],
      '3_hour' => [
        3,
        "HOURS",
        3 * 3600,
      ],
      '1_day' => [
        1,
        "DAYS",
        86400,
      ],
      '3_days' => [
        3,
        "DAYS",
        3 * 86400,
      ],
      '1_week' => [
        1,
        "WEEKS",
        604800,
      ],
      '3_weeks' => [
        3,
        "WEEKS",
        3 * 604800,
      ],
    ];
  }

  /**
   * @dataProvider dataForTestDelayActionInvalidatesOutsideOfBoundaries
   */
  public function testDelayActionInvalidatesOutsideOfBoundaries(int $delay, ?string $expectation) {
    $step = new Step(
      '1',
      'core:delay',
      'core:delay',
      [
        'delay' => $delay,
        'delay_type' => "HOURS",
      ],
      [new NextStep('next-step')]
    );
    $automation = $this->createMock(Automation::class);
    $actionScheduler = $this->createMock(ActionScheduler::class);

    $testee = new DelayAction($actionScheduler);
    try {
      $testee->validate(new StepValidationArgs($automation, $step, []));
    } catch (\Throwable $error) {
      if (!$expectation || !$error instanceof ValidationException) {
        throw $error;
      }
      $this->assertSame($expectation, $error->getErrors()['delay']);
    }
  }

  public function dataForTestDelayActionInvalidatesOutsideOfBoundaries(): array {
    return [
      'zero' => [
        0,
        'A delay must have a positive value',
      ],
      'minus_one' => [
        -1,
        'A delay must have a positive value',
      ],
      'one' => [
        1,
        null,
      ],
      'two_years' => [
        2 * 8760 + 1,
        'A delay can\'t be longer than two years',
      ],
      'below_two_years' => [
        2 * 8760,
        null,
      ],
    ];
  }
}
