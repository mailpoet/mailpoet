<?php declare(strict_types = 1);

namespace MailPoet\REST;

use MailPoetTest;
use RuntimeException;

use function json_decode;

abstract class Test extends MailPoetTest {
  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function get(string $path, array $options = []) {
    return $this->request($path, 'GET', $options);
  }

  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function post(string $path, array $options = []) {
    return $this->request($path, 'POST', $options);
  }

  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function put(string $path, array $options = []) {
    return $this->request($path, 'PUT', $options);
  }

  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function patch(string $path, array $options = []) {
    return $this->request($path, 'PATCH', $options);
  }

  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function delete(string $path, array $options = []) {
    return $this->request($path, 'DELETE', $options);
  }

  /** @param array{query?: array, post?: array, json?: array} $options */
  protected function request(string $path, string $method, array $options = []) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

    // Reset request state so params from a previous request in the same test
    // (e.g. a POST body) don't leak into the next one.
    $_GET = $options['query'] ?? [];
    $_POST = $options['post'] ?? [];
    $GLOBALS['HTTP_RAW_POST_DATA'] = isset($options['json']) ? json_encode($options['json']) : '';

    $server = rest_get_server();
    ob_start();
    $server->serve_request($path);
    $response = ob_get_clean();

    if (!$response) {
      throw new RuntimeException();
    }

    $value = json_decode($response, true);
    $error = json_last_error();
    if ($error) {
      throw new RuntimeException(json_last_error_msg(), $error);
    }
    return $value;
  }
}
