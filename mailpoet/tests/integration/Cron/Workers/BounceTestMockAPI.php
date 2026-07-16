<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\Bounce;

use MailPoet\Services\Bridge\BouncesReportException;

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

  /**
   * Status the simulated failure carries; 0 stands for a failure with no
   * response to read a status from, matching the real API.
   *
   * @var int
   */
  public $failResponseCode = 0;

  /**
   * @return array{recipients: string[], page: int, has_more: bool}
   * @throws BouncesReportException
   */
  public function getBouncesReport(\DateTimeInterface $from, \DateTimeInterface $to, int $page = 1): array {
    $this->getBouncesReportCalls[] = ['from' => $from, 'to' => $to, 'page' => $page];
    if ($this->failResponse) {
      throw BouncesReportException::create()->withCode($this->failResponseCode);
    }
    $recipients = $this->reportPages[$page] ?? [];
    return [
      'recipients' => $recipients,
      'page' => $page,
      'has_more' => isset($this->reportPages[$page + 1]),
    ];
  }
}
