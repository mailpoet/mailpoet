<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class AutomationStatistics {

  private $automationId;
  private $versionId;
  private $entered;
  private $inProgress;
  private $emailsSent;
  private $emailsOpened;
  private $emailsClicked;
  private $orders;
  private $revenue;

  public function __construct(
    int $automationId,
    int $entered = 0,
    int $inProcess = 0,
    ?int $versionId = null,
    int $emailsSent = 0,
    int $emailsOpened = 0,
    int $emailsClicked = 0,
    int $orders = 0,
    float $revenue = 0.0
  ) {
    $this->automationId = $automationId;
    $this->entered = $entered;
    $this->inProgress = $inProcess;
    $this->versionId = $versionId;
    $this->emailsSent = $emailsSent;
    $this->emailsOpened = $emailsOpened;
    $this->emailsClicked = $emailsClicked;
    $this->orders = $orders;
    $this->revenue = $revenue;
  }

  public function getAutomationId(): int {
    return $this->automationId;
  }

  public function getVersionId(): ?int {
    return $this->versionId;
  }

  public function getEntered(): int {
    return $this->entered;
  }

  public function getInProgress(): int {
    return $this->inProgress;
  }

  public function getExited(): int {
    return $this->getEntered() - $this->getInProgress();
  }

  public function getEmailsSent(): int {
    return $this->emailsSent;
  }

  public function getEmailsOpened(): int {
    return $this->emailsOpened;
  }

  public function getEmailsClicked(): int {
    return $this->emailsClicked;
  }

  public function getOrders(): int {
    return $this->orders;
  }

  public function getRevenue(): float {
    return $this->revenue;
  }

  public function toArray(): array {
    return [
      'automation_id' => $this->getAutomationId(),
      'totals' => [
        'entered' => $this->getEntered(),
        'in_progress' => $this->getInProgress(),
        'exited' => $this->getExited(),
      ],
      'emails' => [
        'sent' => $this->getEmailsSent(),
        'opened' => $this->getEmailsOpened(),
        'clicked' => $this->getEmailsClicked(),
        'revenue' => [
          'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
          'value' => $this->getRevenue(),
          'count' => $this->getOrders(),
        ],
      ],
    ];
  }
}
