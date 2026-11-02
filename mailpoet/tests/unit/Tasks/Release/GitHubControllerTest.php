<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailPoetTasks\Release\GitHubController;

class GitHubControllerTest extends \MailPoetUnitTest {
  public function testItPassesWhenStatusesAndCheckRunsPassed() {
    $controller = $this->makeController([
      ['name' => 'PHPStan', 'status' => 'completed', 'conclusion' => 'success'],
      ['name' => 'PHP unit tests', 'status' => 'completed', 'conclusion' => 'success'],
      ['name' => 'JS tests', 'status' => 'completed', 'conclusion' => 'success'],
      ['name' => 'Notify Slack on failure', 'status' => 'completed', 'conclusion' => 'skipped'],
    ]);

    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenACheckRunFailed() {
    $controller = $this->makeController([
      ['name' => 'PHPStan', 'status' => 'completed', 'conclusion' => 'failure'],
      ['name' => 'PHP unit tests', 'status' => 'completed', 'conclusion' => 'success'],
      ['name' => 'JS tests', 'status' => 'completed', 'conclusion' => 'success'],
    ]);

    $this->expectExceptionMessage('PHPStan');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenACheckRunIsStillRunning() {
    $controller = $this->makeController([
      ['name' => 'PHPStan', 'status' => 'completed', 'conclusion' => 'success'],
      ['name' => 'PHP unit tests', 'status' => 'in_progress', 'conclusion' => null],
      ['name' => 'JS tests', 'status' => 'completed', 'conclusion' => 'success'],
    ]);

    $this->expectExceptionMessage('PHP unit tests');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenTheGitHubChecksDidNotRun() {
    $controller = $this->makeController([
      ['name' => 'CodeQL', 'status' => 'completed', 'conclusion' => 'success'],
    ]);

    $this->expectExceptionMessage('PHPStan, PHP unit tests, JS tests');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItDoesNotRequireGitHubChecksForPremium() {
    // Premium’s tests run on CircleCI; its GitHub workflows never gated a release.
    $controller = $this->makeController([
      ['name' => 'Plugin Check', 'status' => 'completed', 'conclusion' => 'failure'],
    ], GitHubController::PROJECT_PREMIUM);

    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  private function makeController(array $checkRuns, string $project = GitHubController::PROJECT_MAILPOET): GitHubController {
    $responses = [
      $this->jsonResponse([[
        'title' => 'Release 5.1.0',
        'statuses_url' => 'https://api.github.com/repos/mailpoet/mailpoet/statuses/abc',
        'head' => ['sha' => 'abc'],
      ]]),
      $this->jsonResponse([['context' => 'ci/circleci: build', 'state' => 'success']]),
      $this->jsonResponse(['total_count' => count($checkRuns), 'check_runs' => $checkRuns]),
    ];
    $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
    return new GitHubController('mailpoet', 'token', $project, $client);
  }

  private function jsonResponse(array $data): Response {
    return new Response(200, [], (string)json_encode($data));
  }
}
