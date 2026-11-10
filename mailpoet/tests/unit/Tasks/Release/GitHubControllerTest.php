<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailPoetTasks\Release\GitHubController;
use Psr\Http\Message\RequestInterface;

class GitHubControllerTest extends \MailPoetUnitTest {
  public function testItPassesWhenStatusesAndAllChecksPassed() {
    // checks.yml runs for the push to release and for the pull request.
    $controller = $this->makeController([
      $this->checkRun('All checks', 'success'),
      $this->checkRun('All checks', 'success'),
      $this->checkRun('Notify Slack on failure', 'skipped'),
    ]);

    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenAllChecksFailed() {
    $controller = $this->makeController([
      $this->checkRun('All checks', 'success'),
      $this->checkRun('All checks', 'failure'),
    ]);

    $this->expectExceptionMessage('All checks');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenAllChecksIsStillRunning() {
    $controller = $this->makeController([
      ['name' => 'All checks', 'status' => 'in_progress', 'conclusion' => null],
    ]);

    $this->expectExceptionMessage('All checks');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItFailsWhenAllChecksDidNotRun() {
    $controller = $this->makeController([
      $this->checkRun('CodeQL', 'success'),
    ]);

    $this->expectExceptionMessage('missing GitHub Actions checks: All checks');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItSeesAFailureAfterTheFirstHundredCheckRuns() {
    // A release pull request has more check runs than one page of the API returns.
    $checkRuns = array_fill(0, 150, $this->checkRun('Acceptance tests / Part 1', 'success'));
    $checkRuns[] = $this->checkRun('All checks', 'failure');
    $controller = $this->makeController($checkRuns);

    $this->expectExceptionMessage('checks failed or are still running: All checks');
    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItIgnoresChecksOutsideTheChecksWorkflow() {
    $controller = $this->makeController([
      $this->checkRun('All checks', 'success'),
      $this->checkRun('WP.org Plugin Check', 'failure'),
    ]);

    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  public function testItDoesNotRequireGitHubChecksForPremium() {
    // Premium’s tests run on CircleCI; its GitHub workflows never gated a release.
    $controller = $this->makeController([
      $this->checkRun('Plugin Check', 'failure'),
    ], GitHubController::PROJECT_PREMIUM);

    $controller->checkReleasePullRequestPassed('5.1.0');
  }

  private function checkRun(string $name, string $conclusion): array {
    return ['name' => $name, 'status' => 'completed', 'conclusion' => $conclusion];
  }

  /**
   * Answers like GitHub: check runs filtered by check_name, at most 100 per page.
   */
  private function makeController(array $checkRuns, string $project = GitHubController::PROJECT_MAILPOET): GitHubController {
    $checkRunsResponse = function (RequestInterface $request) use ($checkRuns): Response {
      parse_str($request->getUri()->getQuery(), $query);
      if (isset($query['check_name'])) {
        $checkRuns = array_values(array_filter($checkRuns, function (array $checkRun) use ($query): bool {
          return $checkRun['name'] === $query['check_name'];
        }));
      }
      $page = array_slice($checkRuns, 0, min(100, (int)($query['per_page'] ?? 30)));
      return $this->jsonResponse(['total_count' => count($checkRuns), 'check_runs' => $page]);
    };
    $responses = [
      $this->jsonResponse([[
        'title' => 'Release 5.1.0',
        'statuses_url' => 'https://api.github.com/repos/mailpoet/mailpoet/statuses/abc',
        'head' => ['sha' => 'abc'],
      ]]),
      $this->jsonResponse([['context' => 'ci/circleci: build', 'state' => 'success']]),
      $checkRunsResponse,
      $checkRunsResponse,
      $checkRunsResponse,
    ];
    $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
    return new GitHubController('mailpoet', 'token', $project, $client);
  }

  private function jsonResponse(array $data): Response {
    return new Response(200, [], (string)json_encode($data));
  }
}
