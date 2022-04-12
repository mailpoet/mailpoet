<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use MailPoet\Automation\Engine\Exceptions\Exception;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Validator\Schema;
use Throwable;
use WP_REST_Request;

class API {
  private const PREFIX = 'mailpoet/v1/automation';
  private const WP_REST_API_INIT_ACTION = 'rest_api_init';

  /** @var EndpointContainer */
  private $endpointContainer;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    EndpointContainer $endpointContainer,
    WordPress $wordPress
  ) {
    $this->endpointContainer = $endpointContainer;
    $this->wordPress = $wordPress;
  }

  public function initialize(): void {
    $this->wordPress->addAction(self::WP_REST_API_INIT_ACTION, function () {
      $this->wordPress->doAction(Hooks::API_INITIALIZE, [$this]);
    });
  }

  public function registerGetRoute(string $route, string $endpoint): void {
    $this->registerRoute($route, $endpoint, 'GET');
  }

  public function registerPostRoute(string $route, string $endpoint): void {
    $this->registerRoute($route, $endpoint, 'POST');
  }

  public function registerPutRoute(string $route, string $endpoint): void {
    $this->registerRoute($route, $endpoint, 'PUT');
  }

  public function registerPatchRoute(string $route, string $endpoint): void {
    $this->registerRoute($route, $endpoint, 'PATCH');
  }

  public function registerDeleteRoute(string $route, string $endpoint): void {
    $this->registerRoute($route, $endpoint, 'DELETE');
  }

  private function registerRoute(string $route, string $endpointClass, string $method): void {
    $schema = array_map(function (Schema $field) {
      return $field->toArray();
    }, $endpointClass::getRequestSchema());

    $this->wordPress->registerRestRoute(self::PREFIX, $route, [
      'methods' => $method,
      'callback' => function (WP_REST_Request $wpRequest) use ($endpointClass) {
        try {
          $endpoint = $this->endpointContainer->get($endpointClass);
          $request = new Request($wpRequest);
          return $endpoint->handle($request);
        } catch (Throwable $e) {
          return $this->convertToErrorResponse($e);
        }
      },
      'permission_callback' => function () use ($endpointClass) {
        $endpoint = $this->endpointContainer->get($endpointClass);
        return $endpoint->checkPermissions();
      },
      'args' => $schema,
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
