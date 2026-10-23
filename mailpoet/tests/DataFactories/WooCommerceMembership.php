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
    $planId = $this->planIdFromOutput($this->tester->cliToString($createCommand));
    $planOut = $this->tester->cliToString(['wc', 'membership_plan', 'get', $planId, '--format=json', '--user=admin']);
    return json_decode($this->lastLine($planOut), true);
  }

  public function createMember(int $userId, int $planId) {
    $createCommand = ['wc', 'user_membership', 'create', '--porcelain', '--user=admin'];
    $createCommand[] = "--customer_id='{$userId}'";
    $createCommand[] = "--plan_id='{$planId}'";
    $membershipId = $this->lastLine($this->tester->cliToString($createCommand));
    $membershipOut = $this->tester->cliToString(['wc', 'user_membership', 'get', $membershipId, '--format=json', '--user=admin']);
    return json_decode($this->lastLine($membershipOut), true);
  }

  // The plan command has no --porcelain: read the ID from "Success: Created Membership Plan 21."
  private function planIdFromOutput(string $output): string {
    foreach (explode("\n", $output) as $line) {
      if (strpos($line, 'Success:') === 0) {
        $line = rtrim(trim($line), '.');
        return substr($line, (int)strrpos($line, ' ') + 1);
      }
    }
    return '';
  }

  // PHP deprecation notices from the plugin can precede the WP-CLI result.
  private function lastLine(string $output): string {
    $lines = explode("\n", trim($output));
    return trim((string)end($lines));
  }
}
