<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class LogsPageData {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function get(): array {
    $dateFrom = (new Carbon())->subDays(7);
    return [
      'logs_default_from' => $dateFrom->format('Y-m-d'),
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'download' => [
        'action_url' => $this->wp->adminUrl('admin-post.php'),
        'nonce' => LogsDownload::createNonce($this->wp),
      ],
    ];
  }
}
