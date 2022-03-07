<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\WP\Functions as WPFunctions;

class SegmentSubscribedTrigger implements Trigger {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getKey(): string {
    return 'mailpoet:segment:subscribed';
  }

  public function getName(): string {
    return __('Subscribed to segment');
  }

  public function getSubjects(): array {
    return [];
  }

  public function registerHooks(): void {
    $this->wp->addAction('mailpoet_segment_subscribed', [$this, 'handleSubscription'], 10, 2);
  }

  public function handleSubscription(): void {
    $this->wp->doAction(Hooks::TRIGGER, $this, []);
  }
}
