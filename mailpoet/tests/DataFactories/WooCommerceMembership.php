<?php

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
    $planOut = $this->tester->cliToString(['wc', 'membership_plan', 'get', $createOutput, '--format=json']);
    return json_decode($planOut, true);
  }

  public function createMember(int $userId, int $planId) {
    $createCommand = ['wc', 'user_membership', 'create'];
    $createCommand[] = "--customer_id='{$userId}'";
    $createCommand[] = "--plan_id='{$userId}'";
    $createOutput = $this->tester->cliToString($createCommand);
    $membershipOut = $this->tester->cliToString(['wc', 'user_membership', 'get', $createOutput, '--format=json']);
    return json_decode($membershipOut, true);
  }
}
