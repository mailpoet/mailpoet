<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use MailPoetTasks\Release\GitHubActionsController;
use MailPoetTasks\Release\GitHubController;
use Psr\Http\Message\RequestInterface;

class GitHubActionsControllerTest extends \MailPoetUnitTest {
  /** @var string[] */
  private $tempFiles = [];

  public function testItDownloadsTheZipOfASuccessfulRunForTheReleaseCommit() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [
        $this->workflowRun(11, 'completed', 'failure'),
        $this->workflowRun(12, 'completed', 'success'),
      ]]),
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => false]]]),
      $this->artifactResponse(['mailpoet.zip' => 'zip content', 'lang/mailpoet.pot' => 'pot content']),
    ], $history);
    $target = $this->tempPath();

    $controller->downloadLatestBuild($target);

    verify(file_get_contents($target))->equals('zip content');
    verify($controller->getDownloadedRunId())->equals(12);
    verify($this->requestLines($history))->equals([
      'GET /repos/mailpoet/mailpoet/actions/workflows/checks.yml/runs?head_sha=abc123&event=push&branch=release&per_page=100',
      'GET /repos/mailpoet/mailpoet/actions/runs/12/artifacts?name=mailpoet-zip',
      'GET /repos/mailpoet/mailpoet/actions/artifacts/7/zip',
    ]);
  }

  public function testItTakesThePushRunNotThePullRequestRunOfTheReleaseCommit() {
    // The release pull request’s run builds the release branch merged with trunk.
    $runs = [
      $this->workflowRun(13, 'completed', 'success', 'pull_request', 'release'),
      $this->workflowRun(12, 'completed', 'success', 'push', 'release'),
    ];
    $runsResponse = function (RequestInterface $request) use ($runs): Response {
      parse_str($request->getUri()->getQuery(), $query);
      $runs = array_values(array_filter($runs, function (array $run) use ($query): bool {
        return (!isset($query['event']) || $run['event'] === $query['event'])
          && (!isset($query['branch']) || $run['head_branch'] === $query['branch']);
      }));
      return $this->jsonResponse(['workflow_runs' => $runs]);
    };
    $controller = $this->makeController([
      $runsResponse,
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => false]]]),
      $this->artifactResponse(['mailpoet.zip' => 'release branch zip', 'lang/mailpoet.pot' => 'pot']),
    ]);
    $target = $this->tempPath();

    $controller->downloadLatestBuild($target);

    verify($controller->getDownloadedRunId())->equals(12);
  }

  public function testItDownloadsThePremiumZipFromThePremiumRepository() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(22, 'completed', 'success')]]),
      $this->jsonResponse(['artifacts' => [['id' => 9, 'expired' => false]]]),
      $this->artifactResponse(['mailpoet-premium.zip' => 'premium zip']),
    ], $history, 60, GitHubController::PROJECT_PREMIUM);
    $target = $this->tempPath();

    $controller->downloadLatestBuild($target);

    verify(file_get_contents($target))->equals('premium zip');
    verify($this->requestLines($history))->equals([
      'GET /repos/mailpoet/mailpoet-premium/actions/workflows/checks.yml/runs?head_sha=abc123&event=push&branch=release&per_page=100',
      'GET /repos/mailpoet/mailpoet-premium/actions/runs/22/artifacts?name=mailpoet-premium-zip',
      'GET /repos/mailpoet/mailpoet-premium/actions/artifacts/9/zip',
    ]);
  }

  public function testItRerunsThePremiumChecks() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(22, 'completed', 'failure')]]),
      new Response(201),
    ], $history, 60, GitHubController::PROJECT_PREMIUM);

    verify($controller->rerunLatestWorkflow())->true();
    verify($this->requestLines($history))->equals([
      'GET /repos/mailpoet/mailpoet-premium/actions/workflows/checks.yml/runs?branch=release&event=push&per_page=1',
      'POST /repos/mailpoet/mailpoet-premium/actions/runs/22/rerun',
    ]);
  }

  /**
   * @dataProvider unsuccessfulRuns
   */
  public function testItRefusesTheZipWhenNoRunPassed(array $run, string $expectedState) {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$run]]),
    ]);
    $target = $this->tempPath();

    try {
      $controller->downloadLatestBuild($target);
      $this->fail('Expected an exception');
    } catch (\Exception $e) {
      verify($e->getMessage())->stringContainsString("is '$expectedState'");
    }
    verify(file_exists($target))->false();
  }

  public function unsuccessfulRuns(): array {
    return [
      'failed' => [$this->workflowRun(11, 'completed', 'failure'), 'failure'],
      'cancelled' => [$this->workflowRun(11, 'completed', 'cancelled'), 'cancelled'],
      'still running' => [$this->workflowRun(11, 'in_progress', null), 'in_progress'],
    ];
  }

  public function testItRefusesTheZipWhenTheReleaseCommitHasNoRun() {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => []]),
    ]);

    $this->expectExceptionMessage('No checks.yml run found for the latest release commit abc123');
    $controller->downloadLatestBuild($this->tempPath());
  }

  public function testItRefusesAnExpiredArtifact() {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'completed', 'success')]]),
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => true]]]),
    ]);

    $this->expectExceptionMessage('has expired. Run it again to build a new one: `gh run rerun 12 -R mailpoet/mailpoet`');
    $controller->downloadLatestBuild($this->tempPath());
  }

  public function testItRefusesARunWithoutTheArtifact() {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'completed', 'success')]]),
      $this->jsonResponse(['artifacts' => []]),
    ]);

    $this->expectExceptionMessage("No 'mailpoet-zip' artifact found in run 12");
    $controller->downloadLatestBuild($this->tempPath());
  }

  public function testItDownloadsThePotFileFromTheGivenRun() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => false]]]),
      $this->artifactResponse(['mailpoet.zip' => 'zip content', 'lang/mailpoet.pot' => 'pot content']),
    ], $history);
    $target = $this->tempPath();

    $controller->downloadPotFile(12, $target);

    verify(file_get_contents($target))->equals('pot content');
    verify($this->requestLines($history)[0])->equals('GET /repos/mailpoet/mailpoet/actions/runs/12/artifacts?name=mailpoet-zip');
  }

  public function testItThrowsAndLeavesNoFileWhenThePotFileIsEmpty() {
    $controller = $this->makeController([
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => false]]]),
      $this->artifactResponse(['mailpoet.zip' => 'zip content', 'lang/mailpoet.pot' => '']),
    ]);
    $target = $this->tempPath();

    try {
      $controller->downloadPotFile(12, $target);
      $this->fail('Expected an exception');
    } catch (\Exception $e) {
      verify($e->getMessage())->stringContainsString('is empty');
    }
    verify(file_exists($target))->false();
    verify(glob($target . '.*'))->empty();
  }

  public function testItKeepsAnExistingFileWhenTheDownloadFails() {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'completed', 'success')]]),
      $this->jsonResponse(['artifacts' => [['id' => 7, 'expired' => false]]]),
      new Response(500, [], 'error'),
    ]);
    $target = $this->tempPath();
    file_put_contents($target, 'previous zip');

    try {
      $controller->downloadLatestBuild($target);
      $this->fail('Expected an exception');
    } catch (\Exception $e) {
      verify(file_get_contents($target))->equals('previous zip');
    }
    verify(glob($target . '.*'))->empty();
  }

  public function testItDoesNotRerunAPassedRun() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'completed', 'success')]]),
    ], $history);

    verify($controller->rerunLatestWorkflow())->false();
    verify(count($history))->equals(1);
  }

  public function testItRerunsAFailedRun() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'completed', 'failure')]]),
      new Response(201),
    ], $history);

    verify($controller->rerunLatestWorkflow())->true();
    verify($this->requestLines($history))->equals([
      'GET /repos/mailpoet/mailpoet/actions/workflows/checks.yml/runs?branch=release&event=push&per_page=1',
      'POST /repos/mailpoet/mailpoet/actions/runs/12/rerun',
    ]);
  }

  public function testItCancelsARunningRunAndWaitsBeforeRerunningIt() {
    $history = new \ArrayObject();
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'in_progress', null)]]),
      new Response(202), // cancel
      $this->jsonResponse($this->workflowRun(12, 'in_progress', null)), // still cancelling
      $this->jsonResponse($this->workflowRun(12, 'completed', 'cancelled')),
      new Response(201), // rerun
    ], $history);

    verify($controller->rerunLatestWorkflow())->true();
    verify($this->requestLines($history))->equals([
      'GET /repos/mailpoet/mailpoet/actions/workflows/checks.yml/runs?branch=release&event=push&per_page=1',
      'POST /repos/mailpoet/mailpoet/actions/runs/12/cancel',
      'GET /repos/mailpoet/mailpoet/actions/runs/12',
      'GET /repos/mailpoet/mailpoet/actions/runs/12',
      'POST /repos/mailpoet/mailpoet/actions/runs/12/rerun',
    ]);
  }

  public function testItThrowsWhenARunNeverFinishes() {
    $controller = $this->makeController([
      $this->jsonResponse(['workflow_runs' => [$this->workflowRun(12, 'in_progress', null)]]),
      new Response(202), // cancel
      $this->jsonResponse($this->workflowRun(12, 'in_progress', null)),
    ], null, 0);

    $this->expectExceptionMessage("Timed out after 0s waiting for run 12 to finish (last status: 'in_progress')");
    $controller->rerunLatestWorkflow();
  }

  public function _after() {
    parent::_after();
    foreach ($this->tempFiles as $file) {
      foreach (array_merge([$file], glob($file . '.*') ?: []) as $path) {
        if (file_exists($path)) {
          unlink($path);
        }
      }
    }
  }

  private function makeController(
    array $responses,
    ?\ArrayObject $history = null,
    int $timeout = 60,
    string $project = GitHubController::PROJECT_MAILPOET
  ): GitHubActionsController {
    $stack = HandlerStack::create(new MockHandler($responses));
    if ($history) {
      $stack->push(Middleware::history($history));
    }
    $repository = $project === GitHubController::PROJECT_PREMIUM ? 'mailpoet-premium' : 'mailpoet';
    $client = new Client(['handler' => $stack, 'base_uri' => "https://api.github.com/repos/mailpoet/$repository/"]);
    $githubController = $this->createMock(GitHubController::class);
    $githubController->method('getLatestCommitRevisionOnBranch')->willReturn('abc123');
    return new class('token', $githubController, $client, $timeout, $project) extends GitHubActionsController {
      /** @var int */
      private $timeout;

      public function __construct(
        string $token,
        GitHubController $githubController,
        Client $client,
        int $timeout,
        string $project
      ) {
        parent::__construct($token, $githubController, $client, $project);
        $this->timeout = $timeout;
      }

      protected function sleep(int $seconds): void {
      }

      protected function getRerunWaitTimeoutSeconds(): int {
        return $this->timeout;
      }
    };
  }

  private function workflowRun(int $id, string $status, ?string $conclusion, string $event = 'push', string $branch = 'release'): array {
    return [
      'id' => $id,
      'status' => $status,
      'conclusion' => $conclusion,
      'event' => $event,
      'head_branch' => $branch,
      'html_url' => "https://github.com/mailpoet/mailpoet/actions/runs/$id",
    ];
  }

  private function artifactResponse(array $files): Response {
    $path = $this->tempPath();
    $zip = new \ZipArchive();
    $zip->open($path, \ZipArchive::CREATE);
    foreach ($files as $name => $content) {
      $zip->addFromString($name, $content);
    }
    $zip->close();
    return new Response(200, [], (string)file_get_contents($path));
  }

  private function jsonResponse(array $data): Response {
    return new Response(200, [], (string)json_encode($data));
  }

  private function tempPath(): string {
    $path = sys_get_temp_dir() . '/mailpoet-actions-test-' . uniqid('', true);
    $this->tempFiles[] = $path;
    return $path;
  }

  private function requestLines(\ArrayObject $history): array {
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> $transactions */
    $transactions = $history->getArrayCopy();
    $lines = [];
    foreach ($transactions as $transaction) {
      $uri = $transaction['request']->getUri();
      $query = $uri->getQuery();
      $lines[] = $transaction['request']->getMethod() . ' ' . $uri->getPath() . ($query !== '' ? '?' . $query : '');
    }
    return $lines;
  }
}
