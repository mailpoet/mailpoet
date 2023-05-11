<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics;

use MailPoet\API\REST\API;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints\OverviewEndpoint;

class RegisterAnalytics {

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  public function register(): void {
    $this->wordPress->addAction(Hooks::API_INITIALIZE, function (API $api) {
      $api->registerPostRoute('automation/analytics/overview', OverviewEndpoint::class);
    });
  }
}
