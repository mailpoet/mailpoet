<?php declare(strict_types = 1);

namespace MailPoet\Automation\API;

use MailPoet\Automation\Exceptions;
use WP_REST_Request;

class Request {
  /** @var WP_REST_Request */
  private $wpRequest;

  public function __construct(
    WP_REST_Request $wpRequest
  ) {
    $this->wpRequest = $wpRequest;
  }

  public function getHeader(string $key): ?string {
    return $this->wpRequest->get_header($key);
  }

  public function getUrlParams(): array {
    return $this->wpRequest->get_url_params();
  }

  public function getUrlParam(string $name): ?string {
    return $this->getUrlParams()[$name] ?? null;
  }

  public function getQueryParams(): array {
    return $this->wpRequest->get_query_params();
  }

  public function getQueryParam(string $name): ?string {
    return $this->getQueryParams()[$name] ?? null;
  }

  public function getBody(): array {
    $json = $this->wpRequest->get_json_params();

    /* @phpstan-ignore-next-line hotfix for missing 'null' in WP annotation */
    if ($json === null) {
      throw Exceptions::apiNoJsonBody();
    }
    return $json;
  }

  public function getRawBody(): string {
    return $this->wpRequest->get_body();
  }
}
