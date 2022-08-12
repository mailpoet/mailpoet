<?php

namespace MailPoet\Test\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;

class DelayActionTest extends \MailPoetTest {

  /**
   * @dataProvider dataForTestItCalculatesDelayTypesCorrectly
   */
  public function testItCalculatesDelayTypesCorrectly(int $delay, string $type, int $expectation) {

    $step = new Step("1", 'core:delay', 'core:delay', 'next-step', [
      'delay' => $delay,
      'delay_type' => $type,
    ]);
    $workflow = $this->createMock(Workflow::class);
    $workflowRun =  $this->createMock(WorkflowRun::class);
    $workflowRun->expects($this->atLeastOnce())->method('getId')->willReturn(1);

    $actionScheduler = $this->createMock(ActionScheduler::class);
    $actionScheduler->expects($this->once())->method('schedule')->with(
      time() + $expectation,
      Hooks::WORKFLOW_STEP,
      [[
        'workflow_run_id' => 1,
        'step_id' => 'next-step',
      ]]
    );
    $testee = new DelayAction($actionScheduler);
    $testee->run(
      $workflow,
      $workflowRun,
      $step
    );
  }

  public function dataForTestItCalculatesDelayTypesCorrectly() : array {
    return [
      '1_hour' => [
        1,
        "HOURS",
        3600,
      ],
      '3_hour' => [
        3,
        "HOURS",
        3*3600,
      ],
      '1_day' => [
        1,
        "DAYS",
        86400,
      ],
      '3_days' => [
        3,
        "DAYS",
        3*86400,
      ],
      '1_week' => [
        1,
        "WEEKS",
        604800,
      ],
      '3_weeks' => [
        3,
        "WEEKS",
        3*604800,
      ],
    ];
  }

  /**
   * @dataProvider dataForTestDelayActionInvalidatesOutsideOfBoundaries
   */
  public function testDelayActionInvalidatesOutsideOfBoundaries(int $delay, bool $expectation) {

    $step = new Step("1", 'core:delay', 'core:delay', 'next-step', [
      'delay' => $delay,
      'delay_type' => "HOURS",
    ]);
    $workflow = $this->createMock(Workflow::class);
    $actionScheduler = $this->createMock(ActionScheduler::class);
    $testee = new DelayAction($actionScheduler);
    $this->assertEquals($expectation, $testee->isValid([], $step, $workflow));
  }

  public function dataForTestDelayActionInvalidatesOutsideOfBoundaries() : array {
    return [
      'zero' => [
        0,
        false,
      ],
      'minus_one' => [
        -1,
        false,
      ],
      'one' => [
        1,
        true,
      ],
      'two_years' => [
        2*8760,
        false,
      ],
      'below_two_years' => [
        2*8760-1,
        true,
      ],
    ];
  }
}
