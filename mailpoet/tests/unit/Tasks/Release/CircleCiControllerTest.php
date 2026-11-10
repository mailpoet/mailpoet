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
  /** @var string[] */
  private $tempFiles = [];

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

  public function testItDownloadsThePremiumZip() {
    $controller = $this->makeZipController([
      ['name' => 'build_release_zip', 'status' => 'success', 'job_number' => 55],
    ], new \ArrayObject());
    $target = $this->tempFile();

    $controller->downloadLatestBuild($target);

    verify(file_get_contents($target))->equals('zip');
  }

  /** @dataProvider workflowStatusProvider */
  public function testItRefusesZipWhenWorkflowIsNotSuccessful(array $workflow, string $expectedMessage) {
    $controller = $this->makeZipController([
      ['name' => 'build_release_zip', 'status' => 'success', 'job_number' => 55],
    ], new \ArrayObject(), $workflow);
    $target = $this->tempFile();

    $caught = null;
    try {
      $controller->downloadLatestBuild($target);
    } catch (\Exception $e) {
      $caught = $e;
    }
    $this->assertInstanceOf(\Exception::class, $caught);
    verify($caught->getMessage())->stringContainsString($expectedMessage);
    verify(file_exists($target))->false();
  }

  public function workflowStatusProvider(): array {
    return [
      // the zip job is green in every case; a test job failed, or is still running
      'failed' => [['id' => 'w1', 'status' => 'failed'], "Workflow has status 'failed'"],
      'failing' => [['id' => 'w1', 'status' => 'failing'], "has status 'failing'"],
      'running' => [['id' => 'w1', 'status' => 'running'], "has status 'running'"],
      'missing' => [['id' => 'w1'], "has status 'missing'"],
    ];
  }

  /** @dataProvider httpErrorProvider */
  public function testItLeavesNoFileWhenDownloadFails(int $status) {
    $controller = $this->makeZipController([
      ['name' => 'build_release_zip', 'status' => 'success', 'job_number' => 55],
    ], new \ArrayObject(), ['id' => 'w1', 'status' => 'success'], new Response($status, [], 'error body'));
    $target = $this->tempFile();

    try {
      $controller->downloadLatestBuild($target);
      $this->fail('Expected an exception.');
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      verify($e->getCode())->equals($status);
    }
    verify(glob($target . '*'))->empty();
  }

  public function httpErrorProvider(): array {
    return ['forbidden' => [403], 'server error' => [500]];
  }

  public function testItKeepsExistingFileWhenDownloadFails() {
    $controller = $this->makeZipController([
      ['name' => 'build_release_zip', 'status' => 'success', 'job_number' => 55],
    ], new \ArrayObject(), ['id' => 'w1', 'status' => 'success'], new Response(500, [], 'error body'));
    $target = $this->tempFile();
    file_put_contents($target, 'old');

    try {
      $controller->downloadLatestBuild($target);
      $this->fail('Expected an exception.');
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      verify(file_get_contents($target))->equals('old');
    }
    verify(glob($target . '*'))->equals([$target]);
  }

  /**
   * @param array<int, array<string, mixed>> $jobs
   */
  private function makeZipController(
    array $jobs,
    \ArrayObject $history,
    array $workflow = ['id' => 'w1', 'status' => 'success'],
    ?Response $zipResponse = null
  ): CircleCiController {
    return $this->makeController([
      $this->jsonResponse(['items' => [['id' => 'p1', 'vcs' => ['revision' => 'abc']]]]),
      $this->jsonResponse(['items' => [$workflow]]),
      $this->jsonResponse(['items' => $jobs]),
      $this->jsonResponse(['items' => [['path' => 'mailpoet-premium.zip', 'url' => 'https://artifacts.test/zip']]]),
      $zipResponse ?? new Response(200, [], 'zip'),
    ], $history);
  }

  private function tempFile(): string {
    $path = sys_get_temp_dir() . '/mailpoet-zip-test-' . uniqid('', true) . '.zip';
    $this->tempFiles[] = $path;
    return $path;
  }

  public function _after() {
    foreach ($this->tempFiles as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
    $this->tempFiles = [];
  }

  private function jsonResponse(array $data): Response {
    return new Response(200, [], (string)json_encode($data));
  }

  /**
   * @param Response[] $responses
   * @param \ArrayObject $history Guzzle history container, populated as requests are sent.
   */
  private function makeController(array $responses, \ArrayObject $history, int $timeout = 600, string $project = CircleCiController::PROJECT_PREMIUM): CircleCiController {
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));
    $client = new Client(['handler' => $stack]);
    $github = $this->makeEmpty(GitHubController::class, ['getLatestCommitRevisionOnBranch' => 'abc']);

    return new class('mailpoet', 'token', $project, $github, $client, $timeout) extends CircleCiController {
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
