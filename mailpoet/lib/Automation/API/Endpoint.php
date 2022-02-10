<?php declare(strict_types = 1);

namespace MailPoet\Automation\API;

abstract class Endpoint {
  public function get(Request $request): Response {
    return $this->methodNotAllowed();
  }

  public function post(Request $request): Response {
    return $this->methodNotAllowed();
  }

  public function put(Request $request): Response {
    return $this->methodNotAllowed();
  }

  public function delete(Request $request): Response {
    return $this->methodNotAllowed();
  }

  private function methodNotAllowed(): ErrorResponse {
    return new ErrorResponse(405, 'Method not allowed', 'mailpoet_automation_method_not_allowed');
  }
}
