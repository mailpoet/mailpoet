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
    $createOutput = $this->tester->cliToString($createCommand);
    preg_match('!\d+!', $createOutput, $matches);
    $planOut = $this->tester->cliToString(['wc', 'membership_plan', 'get', reset($matches), '--format=json', '--user=admin']);
    return json_decode($planOut, true);
  }

  public function createMember(int $userId, int $planId) {
    $createCommand = ['wc', 'user_membership', 'create', '--user=admin'];
    $createCommand[] = "--customer_id='{$userId}'";
    $createCommand[] = "--plan_id='{$planId}'";
    $createOutput = $this->tester->cliToString($createCommand);
    preg_match('!\d+!', $createOutput, $matches);
    $membershipOut = $this->tester->cliToString(['wc', 'user_membership', 'get', reset($matches), '--format=json', '--user=admin']);
    return json_decode($membershipOut, true);
  }
}
