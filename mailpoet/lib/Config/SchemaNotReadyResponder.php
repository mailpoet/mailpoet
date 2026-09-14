<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\API\REST\API as RestApi;
use MailPoet\Router\Router;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;
use WP_REST_Request;

/**
 * Answers MailPoet's entry points while the schema is not ready.
 *
 * Initializer skips all plugin wiring in that state, so without these handlers an ajax
 * call would get WordPress's bare "0", a REST call a 404 for an unknown namespace, and
 * a router link (unsubscribe, tracking, cron) or a form post the site's home page.
 * All of those look like a broken install; a 503 with the reason does not.
 *
 * Migration errors can name tables and queries, so the detailed message goes to
 * administrators only; everyone else gets the generic one.
 */
class SchemaNotReadyResponder {
  private const ADMIN_POST_ACTIONS = [
    'mailpoet_subscription_form',
    'mailpoet_subscription_update',
  ];

  private SchemaState $schemaState;

  private WPFunctions $wp;

  public function __construct(
    SchemaState $schemaState,
    WPFunctions $wp
  ) {
    $this->schemaState = $schemaState;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->wp->addAction('wp_ajax_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addAction('wp_ajax_nopriv_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addFilter('rest_pre_dispatch', [$this, 'rejectRestRequest'], 10, 3);
    $this->wp->addAction('wp_loaded', [$this, 'rejectRouterRequest']);
    foreach (self::ADMIN_POST_ACTIONS as $action) {
      $this->wp->addAction('admin_post_' . $action, [$this, 'sendUnavailablePage']);
      $this->wp->addAction('admin_post_nopriv_' . $action, [$this, 'sendUnavailablePage']);
    }
  }

  public function buildJsonResponse(): ErrorResponse {
    return new ErrorResponse(
      [$this->getErrorCode() => $this->getMessage()],
      [],
      Response::STATUS_SERVICE_UNAVAILABLE
    );
  }

  public function sendJsonResponse(): void {
    $this->buildJsonResponse()->send();
  }

  /**
   * @param mixed $result
   * @param mixed $server
   * @param mixed $request
   * @return mixed
   */
  public function rejectRestRequest($result, $server, $request) {
    if ($result !== null || !$request instanceof WP_REST_Request) {
      return $result;
    }
    if (!$this->isMailPoetRoute($request->get_route())) {
      return $result;
    }
    return new WP_Error(
      'mailpoet_' . $this->getErrorCode(),
      $this->getMessage(),
      ['status' => Response::STATUS_SERVICE_UNAVAILABLE]
    );
  }

  public function rejectRouterRequest(): void {
    if (!isset($_GET[Router::NAME])) {
      return;
    }
    $this->sendUnavailablePage();
  }

  public function sendUnavailablePage(): void {
    $this->wp->wpDie(
      esc_html($this->getMessage()),
      '',
      ['response' => Response::STATUS_SERVICE_UNAVAILABLE]
    );
  }

  private function isMailPoetRoute(string $route): bool {
    $namespace = '/' . RestApi::PREFIX;
    return $route === $namespace || strpos($route, $namespace . '/') === 0;
  }

  /**
   * The detailed message for administrators, the generic one for everyone else.
   */
  public function getMessage(): string {
    return $this->wp->currentUserCan('manage_options')
      ? $this->schemaState->getMessage()
      : $this->schemaState->getPublicMessage();
  }

  private function getErrorCode(): string {
    return $this->schemaState->getStatus() === SchemaState::STATUS_FAILED
      ? Error::UPDATE_FAILED
      : Error::UPDATE_IN_PROGRESS;
  }
}
