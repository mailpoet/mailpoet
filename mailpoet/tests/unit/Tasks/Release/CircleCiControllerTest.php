<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use MailPoetTasks\Release\CircleCiController;
use MailPoetTasks\Release\GitHubController;

class CircleCiControllerTest extends \MailPoetUnitTest {
  public function testItCancelsPollsAndRerunsRunningWorkflow() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1']]]),
      $this->jsonResponse(['items' => [['id' => 'w1', 'status' => 'running']]]),
      $this->jsonResponse(['message' => 'Accepted.']), // cancel
      $this->jsonResponse(['id' => 'w1', 'status' => 'running']), // still cancelling
      $this->jsonResponse(['id' => 'w1', 'status' => 'canceled']), // now terminal
      $this->jsonResponse(['workflow_id' => 'w1']), // rerun
    ], $history);

    $result = $controller->rerunLatestWorkflow(CircleCiController::PROJECT_PREMIUM);

    verify($result)->true();
    // The running workflow is cancelled, then polled until it reaches a terminal
    // state, and only then rerun. The two status fetches before the rerun are the
    // guard against the original bug (rerunning while still non-terminal).
    verify($this->requestLines($history))->equals([
      'GET /api/v2/project/gh/mailpoet/mailpoet-premium/pipeline',
      'GET /api/v2/pipeline/p1/workflow',
      'POST /api/v2/workflow/w1/cancel',
      'GET /api/v2/workflow/w1',
      'GET /api/v2/workflow/w1',
      'POST /api/v2/workflow/w1/rerun',
    ]);
  }

  public function testItWaitsForFailingWorkflowToFinishThenReruns() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1']]]),
      $this->jsonResponse(['items' => [['id' => 'w1', 'status' => 'failing']]]),
      $this->jsonResponse(['id' => 'w1', 'status' => 'failing']), // still running remaining jobs
      $this->jsonResponse(['id' => 'w1', 'status' => 'failed']), // terminal
      $this->jsonResponse(['workflow_id' => 'w1']), // rerun
    ], $history);

    $result = $controller->rerunLatestWorkflow(CircleCiController::PROJECT_PREMIUM);

    verify($result)->true();
    // A failing workflow is not cancelled, but it is polled until its remaining
    // jobs finish (terminal 'failed') before the rerun.
    verify($this->requestLines($history))->equals([
      'GET /api/v2/project/gh/mailpoet/mailpoet-premium/pipeline',
      'GET /api/v2/pipeline/p1/workflow',
      'GET /api/v2/workflow/w1',
      'GET /api/v2/workflow/w1',
      'POST /api/v2/workflow/w1/rerun',
    ]);
  }

  public function testItRerunsAlreadyTerminalWorkflowWithoutPolling() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1']]]),
      $this->jsonResponse(['items' => [['id' => 'w1', 'status' => 'failed']]]),
      $this->jsonResponse(['id' => 'w1', 'status' => 'failed']), // single terminal check
      $this->jsonResponse(['workflow_id' => 'w1']), // rerun
    ], $history);

    $result = $controller->rerunLatestWorkflow(CircleCiController::PROJECT_PREMIUM);

    verify($result)->true();
    // An already-terminal workflow is fetched once (the wait returns immediately),
    // is not cancelled, and is rerun right after that single status fetch.
    verify($this->requestLines($history))->equals([
      'GET /api/v2/project/gh/mailpoet/mailpoet-premium/pipeline',
      'GET /api/v2/pipeline/p1/workflow',
      'GET /api/v2/workflow/w1',
      'POST /api/v2/workflow/w1/rerun',
    ]);
  }

  public function testItDoesNotRerunSuccessfulWorkflow() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1']]]),
      $this->jsonResponse(['items' => [['id' => 'w1', 'status' => 'success']]]),
    ], $history);

    $result = $controller->rerunLatestWorkflow(CircleCiController::PROJECT_PREMIUM);

    verify($result)->false();
    // A successful workflow needs no rerun, so nothing happens beyond the two
    // lookups that read its status: no cancel, no poll, no rerun.
    verify($this->requestLines($history))->equals([
      'GET /api/v2/project/gh/mailpoet/mailpoet-premium/pipeline',
      'GET /api/v2/pipeline/p1/workflow',
    ]);
  }

  public function testItThrowsWhenWorkflowNeverReachesTerminalState() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1']]]),
      $this->jsonResponse(['items' => [['id' => 'w1', 'status' => 'failing']]]),
      $this->jsonResponse(['id' => 'w1', 'status' => 'failing']),
    ], $history, 0);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches('/terminal state/');
    $controller->rerunLatestWorkflow(CircleCiController::PROJECT_PREMIUM);
  }

  private function jsonResponse(array $data): Response {
    return new Response(200, [], (string)json_encode($data));
  }

  /**
   * @param Response[] $responses
   * @param \ArrayObject $history Guzzle history container, populated as requests are sent.
   */
  private function makeController(array $responses, \ArrayObject $history, int $timeout = 600): CircleCiController {
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));
    $client = new Client(['handler' => $stack]);
    $github = $this->makeEmpty(GitHubController::class);

    return new class('mailpoet', 'token', CircleCiController::PROJECT_PREMIUM, $github, $client, $timeout) extends CircleCiController {
      /** @var int */
      private $testTimeout;

      public function __construct(
        $username,
        $token,
        $project,
        GitHubController $githubController,
        Client $httpClient,
        int $timeout
      ) {
        parent::__construct($username, $token, $project, $githubController, $httpClient);
        $this->testTimeout = $timeout;
      }

      protected function sleep(int $seconds): void {
      }

      protected function getRerunWaitTimeoutSeconds(): int {
        return $this->testTimeout;
      }
    };
  }

  /**
   * @param \ArrayObject $history
   * @return string[] Each entry is "METHOD path".
   */
  private function requestLines(\ArrayObject $history): array {
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> $transactions */
    $transactions = $history->getArrayCopy();
    $lines = [];
    foreach ($transactions as $transaction) {
      $request = $transaction['request'];
      $lines[] = $request->getMethod() . ' ' . $request->getUri()->getPath();
    }
    return $lines;
  }
}
