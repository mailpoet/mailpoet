<?php

namespace MailPoetTasks\Release;

class HttpClient {
  /** @var string|null */
  private $base_uri;

  public function __construct($base_uri = null) {
    $this->base_uri = $base_uri === null ? null : rtrim($base_uri, '/');
  }

  public function get($url) {
    return $this->request($url, 'GET');
  }

  public function post($url, array $data) {
    return $this->request($url, 'POST', $data);
  }

  public function put($url, array $data) {
    return $this->request($url, 'PUT', $data);
  }

  private function request($path, $method, array $data = null) {
    $url = $this->base_uri === null ? $path : ("$this->base_uri/" . ltrim($path, '/'));
    $options = [];
    if ($method === 'POST' || $method === 'PUT') {
      $options = [
        'http' => [
          'method' => $method,
          'header' => "Content-type: application/json\r\n",
          'content' => json_encode($data),
        ]
      ];
    }
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === false) {
      $error = error_get_last();
      throw new \Exception('Request error: ' . $error['message']);
    }
    return json_decode($result, true);
  }
}
