<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Analytics;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\REST\Automation\AutomationTest;
use MailPoet\Test\DataFactories\AutomationRun as AutomationRunFactory;

require_once __DIR__ . '/../AutomationTest.php';

class UpdateRunStatusEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/analytics/runs/%d/status';

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRun */
  private $runningRun;

  /** @var AutomationRun */
  private $cancelledRun;

  public function _before() {
    parent::_before();
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);

    // Create a running automation run
    $this->runningRun = (new AutomationRunFactory())
      ->withStatus(AutomationRun::STATUS_RUNNING)
      ->create();

    // Create a cancelled automation run
    $this->cancelledRun = (new AutomationRunFactory())
      ->withStatus(AutomationRun::STATUS_CANCELLED)
      ->create();
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_CANCELLED,
        ],
      ]
    );

    $this->assertSame(AutomationRun::STATUS_CANCELLED, $data['data']['status']);

    $updatedRun = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $updatedRun);
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $updatedRun->getStatus());
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_CANCELLED,
        ],
      ]
    );

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $run = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $run->getStatus());
  }

  public function testItUpdatesStatusFromRunningToCancelled(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_CANCELLED,
        ],
      ]
    );

    $this->assertSame(AutomationRun::STATUS_CANCELLED, $data['data']['status']);
    $this->assertArrayHasKey('id', $data['data']);
    $this->assertArrayHasKey('updated_at', $data['data']);

    $updatedRun = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $updatedRun);
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $updatedRun->getStatus());
  }

  public function testItUpdatesStatusFromCancelledToRunning(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->cancelledRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_RUNNING,
        ],
      ]
    );

    $this->assertSame(AutomationRun::STATUS_RUNNING, $data['data']['status']);

    $updatedRun = $this->automationRunStorage->getAutomationRun($this->cancelledRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $updatedRun);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $updatedRun->getStatus());
  }

  public function testItReturnsSameStatusWhenNoChangeNeeded(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_RUNNING,
        ],
      ]
    );

    $this->assertSame(AutomationRun::STATUS_RUNNING, $data['data']['status']);

    $run = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $run->getStatus());
  }

  public function testItReturnsErrorWhenStatusParameterMissing(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [],
      ]
    );
    $this->assertSame('rest_missing_callback_param', $data['code']);
    $this->assertSame('Missing parameter(s): status', $data['message']);
  }

  public function testItReturnsErrorWhenRunNotFound(): void {
    $nonExistentId = 99999;
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $nonExistentId),
      [
        'json' => [
          'status' => AutomationRun::STATUS_CANCELLED,
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_run_not_found', $data['code']);
  }

  public function testItReturnsErrorWhenInvalidStatusValue(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => 'invalid-status',
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_unknown_error', $data['code']);
    $this->assertArrayHasKey('errors', $data['data']);
    $this->assertArrayHasKey('status', $data['data']['errors']);
  }

  public function testItReturnsErrorWhenInvalidTransition(): void {
    $completeRun = (new AutomationRunFactory())
      ->withStatus(AutomationRun::STATUS_COMPLETE)
      ->create();

    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $completeRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_RUNNING,
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_unknown_error', $data['code']);
    $this->assertArrayHasKey('errors', $data['data']);
    $this->assertArrayHasKey('status', $data['data']['errors']);

    $run = $this->automationRunStorage->getAutomationRun($completeRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $run->getStatus());
  }

  public function testItReturnsErrorWhenTransitioningToComplete(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_COMPLETE,
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_unknown_error', $data['code']);

    $run = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $run->getStatus());
  }

  public function testItReturnsErrorWhenTransitioningToFailed(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->runningRun->getId()),
      [
        'json' => [
          'status' => AutomationRun::STATUS_FAILED,
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_unknown_error', $data['code']);

    $run = $this->automationRunStorage->getAutomationRun($this->runningRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $run->getStatus());
  }
}
