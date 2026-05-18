<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

/**
 * Captures the link-matching strategy for the email click segment filter.
 *
 * Standard newsletters match by `statistics_clicks.link_id`. Automation
 * newsletters match by `LOWER(newsletter_links.url)` because each send
 * inserts a new link row with a fresh hash for the same URL, so the
 * stored link_id alone can't capture "clicked the URL the user picked
 * in the UI" across sends.
 */
final class EmailLinkFilter {
  /** @var bool */
  private $isAutomationNewsletter;

  /** @var int[] */
  private $linkIds;

  /** @var string[] */
  private $linkUrls;

  /** @var string */
  private $statsLinkAlias;

  /**
   * @param int[] $linkIds
   * @param string[] $linkUrls
   */
  public function __construct(
    bool $isAutomationNewsletter,
    array $linkIds,
    array $linkUrls,
    string $statsLinkAlias
  ) {
    $this->isAutomationNewsletter = $isAutomationNewsletter;
    $this->linkIds = $linkIds;
    $this->linkUrls = $linkUrls;
    $this->statsLinkAlias = $statsLinkAlias;
  }

  public function isAutomationNewsletter(): bool {
    return $this->isAutomationNewsletter;
  }

  public function hasSpecificLinks(): bool {
    return (bool)$this->linkIds || (bool)$this->linkUrls;
  }

  public function matchesByUrl(): bool {
    return (bool)$this->linkUrls;
  }

  /**
   * "All of" with no specific picks on an automation: count by distinct URL
   * because each send duplicates the URL across fresh link rows. Caller is
   * expected to use this in an "all of" context.
   */
  public function aggregatesAllLinksByUrl(): bool {
    return $this->isAutomationNewsletter && !$this->hasSpecificLinks();
  }

  /**
   * Whether the stats query needs to join `newsletter_links`. Either because
   * we match by URL (and need the URL column), or because the "all of" count
   * needs to deduplicate URLs across multiple automation sends.
   */
  public function needsLinkJoin(bool $isAllOperator): bool {
    return $this->matchesByUrl() || ($isAllOperator && $this->aggregatesAllLinksByUrl());
  }

  /** @return int[] */
  public function getLinkIds(): array {
    return $this->linkIds;
  }

  /** @return string[] */
  public function getLinkUrls(): array {
    return $this->linkUrls;
  }

  public function getStatsLinkAlias(): string {
    return $this->statsLinkAlias;
  }
}
