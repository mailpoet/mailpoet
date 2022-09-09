<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use MailPoet\API\REST\API as MailPoetApi;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\WordPress;

class API {
  private const PREFIX = 'automation/';

  /** @var MailPoetApi */
  private $api;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    MailPoetApi $api,
    WordPress $wordPress
  ) {
    $this->api = $api;
    $this->wordPress = $wordPress;
  }

  public function initialize(): void {
    $this->wordPress->addAction(MailPoetApi::REST_API_INIT_ACTION, function () {
      $this->wordPress->doAction(Hooks::API_INITIALIZE, [$this]);
    });
  }

  public function registerGetRoute(string $route, string $endpoint): void {
    $this->api->registerGetRoute(self::PREFIX . $route, $endpoint);
  }

  public function registerPostRoute(string $route, string $endpoint): void {
    $this->api->registerPostRoute(self::PREFIX . $route, $endpoint);
  }

  public function registerPutRoute(string $route, string $endpoint): void {
    $this->api->registerPutRoute(self::PREFIX . $route, $endpoint);
  }

  public function registerPatchRoute(string $route, string $endpoint): void {
    $this->api->registerPatchRoute(self::PREFIX . $route, $endpoint);
  }

  public function registerDeleteRoute(string $route, string $endpoint): void {
    $this->api->registerDeleteRoute(self::PREFIX . $route, $endpoint);
  }
}
