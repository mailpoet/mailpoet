<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

class WooCommerceMembership {
  /** @var \AcceptanceTester */
  private $tester;

  public function __construct(
    \AcceptanceTester $tester
  ) {
    $this->tester = $tester;
  }

  public function createPlan($name) {
    $createCommand = ['wc', 'memberships', 'plan', 'create'];
    $createCommand[] = "--name='{$name}'";
    $planId = $this->createdId($this->tester->cliToString($createCommand));
    $planOut = $this->tester->cliToString(['wc', 'membership_plan', 'get', $planId, '--format=json', '--user=admin']);
    return json_decode($this->lastLine($planOut), true);
  }

  public function createMember(int $userId, int $planId) {
    $createCommand = ['wc', 'user_membership', 'create', '--user=admin'];
    $createCommand[] = "--customer_id='{$userId}'";
    $createCommand[] = "--plan_id='{$planId}'";
    $membershipId = $this->createdId($this->tester->cliToString($createCommand));
    $membershipOut = $this->tester->cliToString(['wc', 'user_membership', 'get', $membershipId, '--format=json', '--user=admin']);
    return json_decode($this->lastLine($membershipOut), true);
  }

  // The create commands have no --porcelain. The ID is the number in the last line, "Success: Created ... 21."
  private function createdId(string $output): string {
    preg_match('/(\d+)\.?$/', $this->lastLine($output), $matches);
    return $matches[1] ?? '';
  }

  // PHP deprecation notices from the plugin can precede the WP-CLI result.
  private function lastLine(string $output): string {
    $lines = explode("\n", trim($output));
    return trim((string)end($lines));
  }
}
