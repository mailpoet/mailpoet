<?php declare(strict_types = 1);

namespace MailPoet\Util\License\Features\Data;

class Capabilities {
  private bool $mailpoetLogoInEmails;
  private bool $detailedAnalytics;
  private int $automationSteps;
  private int $segmentFilters;

  public function __construct(
    bool $mailpoetLogoInEmails = true,
    bool $detailedAnalytics = false,
    int $automationSteps = 1,
    int $segmentFilters = 1
  ) {
    $this->mailpoetLogoInEmails = $mailpoetLogoInEmails;
    $this->detailedAnalytics = $detailedAnalytics;
    $this->automationSteps = $automationSteps;
    $this->segmentFilters = $segmentFilters;
  }

  /**
   * @return bool True if Mailpoet logo is required in emails
   */
  public function getMailpoetLogoInEmails(): bool {
    return $this->mailpoetLogoInEmails;
  }

  /**
   * @return bool True if Detailed analytics are enabled
   */
  public function getDetailedAnalytics(): bool {
    return $this->detailedAnalytics;
  }

  /**
   * @return int Automation steps limit, 0 if unlimited
   */
  public function getAutomationSteps(): int {
    return $this->automationSteps;
  }

  /**
   * @return int Segment filters limit, 0 if unlimited
   */
  public function getSegmentFilters(): int {
    return $this->segmentFilters;
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'mailpoetLogoInEmails' => [
        'isRestricted' => $this->mailpoetLogoInEmails,
        'type' => 'boolean',
        ],
      'detailedAnalytics' => [
        'isRestricted' => !$this->detailedAnalytics,
        'type' => 'boolean',
      ],
      'automationSteps' => [
        'isRestricted' => $this->automationSteps > 0,
        'type' => 'number',
        'value' => $this->automationSteps,
      ],
      'segmentFilters' => [
        'type' => 'number',
        'isRestricted' => $this->segmentFilters > 0,
        'value' => $this->segmentFilters,
      ],
    ];
  }
}
