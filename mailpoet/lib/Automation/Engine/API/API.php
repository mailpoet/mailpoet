<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use MailPoet\Automation\Engine\API\Endpoints\SystemDatabaseEndpoint;
use MailPoet\Automation\Engine\API\Endpoints\WorkflowsEndpoint;
use MailPoet\Automation\Engine\Exceptions\Exception;
use MailPoet\Automation\Engine\WordPress;
use ReflectionClass;
use Throwable;

class API {
  private const PREFIX = 'mailpoet/v1/automation';
  private const METHODS = ['get', 'post', 'put', 'delete'];
  private const WP_REST_API_INIT_ACTION = 'rest_api_init';

  /** @var EndpointFactory */
  private $endpointFactory;

  /** @var WordPress */
  private $wordPress;

  /** @var array<string, class-string<Endpoint>> */
  private $routes = [
    'system/database' => SystemDatabaseEndpoint::class,
    'workflows' => WorkflowsEndpoint::class,
  ];

  public function __construct(
    EndpointFactory $endpointFactory,
    WordPress $wordPress
  ) {
    $this->endpointFactory = $endpointFactory;
    $this->wordPress = $wordPress;
  }

  public function initialize(): void {
    $this->wordPress->addAction(self::WP_REST_API_INIT_ACTION, function () {
      foreach ($this->routes as $route => $endpoint) {
        $reflection = new ReflectionClass($endpoint);
        foreach (self::METHODS as $method) {
          if ($reflection->hasMethod($method) && $reflection->getMethod($method)->class === $endpoint) {
            $this->registerRoute($route, $endpoint, $method);
          }
        }
      }
    });
  }

  private function registerRoute(string $route, string $endpoint, string $method): void {
    $this->wordPress->registerRestRoute(self::PREFIX, $route, [
      'methods' => strtoupper($method),
      'callback' => function ($wpRequest) use ($endpoint, $method) {
        try {
          $request = new Request($wpRequest);
          return $this->endpointFactory->createEndpoint($endpoint)->$method($request);
        } catch (Throwable $e) {
          return $this->convertToErrorResponse($e);
        }
      },
      'permission_callback' => function () {
        return $this->wordPress->currentUserCan('administrator');
      },
    ]);
  }

  private function convertToErrorResponse(Throwable $e): ErrorResponse {
    $response = $e instanceof Exception
      ? new ErrorResponse($e->getStatusCode(), $e->getMessage(), $e->getErrorCode())
      : new ErrorResponse(500, __('An unknown error occurred.', 'mailpoet'), 'mailpoet_automation_unknown_error');

    if ($response->get_status() >= 500) {
      error_log((string)$e); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
    }
    return $response;
  }
}
