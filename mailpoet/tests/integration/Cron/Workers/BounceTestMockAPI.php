<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\Bounce;

class BounceTestMockAPI {
  /** @var array<int, array{from: \DateTimeInterface, to: \DateTimeInterface, page: int}> */
  public $getBouncesReportCalls = [];

  /**
   * Pages of recipients returned by getBouncesReport(), keyed by 1-based page
   * number. Defaults to a single page with the hard bounce address.
   *
   * @var array<int, string[]>
   */
  public $reportPages = [
    1 => ['hard_bounce@example.com'],
  ];

  /** @var bool When true, getBouncesReport() simulates a failed request. */
  public $failResponse = false;

  /** @return array{recipients: string[], page: int, has_more: bool}|null */
  public function getBouncesReport(\DateTimeInterface $from, \DateTimeInterface $to, int $page = 1): ?array {
    $this->getBouncesReportCalls[] = ['from' => $from, 'to' => $to, 'page' => $page];
    if ($this->failResponse) {
      return null;
    }
    $recipients = $this->reportPages[$page] ?? [];
    return [
      'recipients' => $recipients,
      'page' => $page,
      'has_more' => isset($this->reportPages[$page + 1]),
    ];
  }
}
