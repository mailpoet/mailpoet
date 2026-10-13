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

  public function testItDownloadsPotFileFromGivenJob() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [
        ['path' => 'home/circleci/mailpoet/mailpoet/mailpoet.zip', 'url' => 'https://artifacts.test/zip'],
        ['path' => 'home/circleci/mailpoet/mailpoet/lang/mailpoet.pot', 'url' => 'https://artifacts.test/pot'],
      ]]),
      new Response(200, [], 'msgid "Hello"'),
    ], $history);
    $target = $this->tempFile();

    $controller->downloadPotFile(1234, $target);

    verify($this->requestLines($history))->equals([
      'GET 1234/artifacts',
      'GET /pot',
    ]);
    verify(file_get_contents($target))->equals('msgid "Hello"');
  }

  public function testItMatchesBarePotArtifactPath() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['path' => 'mailpoet.pot', 'url' => 'https://artifacts.test/bare']]]),
      new Response(200, [], 'content'),
    ], $history);

    $controller->downloadPotFile(1, $this->tempFile());

    verify($this->requestLines($history)[1])->equals('GET /bare');
  }

  public function testItDoesNotMatchSimilarlyNamedArtifacts() {
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [
        ['path' => 'home/circleci/lang/mailpoet.pot.bak', 'url' => 'https://artifacts.test/bak'],
        ['path' => 'home/circleci/lang/foo-mailpoet.pot', 'url' => 'https://artifacts.test/foo'],
      ]]),
    ], new \ArrayObject());

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No artifact 'mailpoet.pot' found in build 7");
    $controller->downloadPotFile(7, $this->tempFile());
  }

  public function testItThrowsAndLeavesNoFileWhenPotFileIsEmpty() {
    $controller = $this->makeController([
      $this->jsonResponse(['items' => [['path' => 'mailpoet.pot', 'url' => 'https://artifacts.test/pot']]]),
      new Response(200, [], ''),
    ], new \ArrayObject());
    $target = $this->tempFile();

    try {
      $controller->downloadPotFile(1, $target);
      $this->fail('Expected an exception for an empty .pot file.');
    } catch (\Exception $e) {
      verify($e->getMessage())->stringContainsString('empty');
    }
    verify(file_exists($target))->false();
  }

  private function tempFile(): string {
    $path = sys_get_temp_dir() . '/mailpoet-pot-test-' . uniqid('', true) . '.pot';
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
