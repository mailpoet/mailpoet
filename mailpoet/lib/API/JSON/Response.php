<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON;

use MailPoet\WP\Functions as WPFunctions;

abstract class Response {
  const STATUS_OK = 200;
  const REDIRECT = 302;
  const STATUS_BAD_REQUEST = 400;
  const STATUS_UNAUTHORIZED = 401;
  const STATUS_FORBIDDEN = 403;
  const STATUS_NOT_FOUND = 404;
  const STATUS_CONFLICT = 409;
  const STATUS_UNKNOWN = 500;

  public $status;
  public $meta;
  public $location;

  public function __construct($status, $meta = [], $location = null) { // phpcs:ignore
    $this->status = $status;
    $this->meta = $meta;
    $this->location = $location;
  }

  public function send() {
    if ($this->status === self::REDIRECT && $this->location) {
      header("Location: " . $this->location, true, $this->status);
      exit;
    }

    WPFunctions::get()->statusHeader($this->status);

    $data = $this->getData();
    $response = [];

    if (!empty($this->meta)) {
      $response['meta'] = $this->meta;
    }
    if ($data === null) {
      $data = [];
    }
    $response = array_merge($response, $data);

    @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
    echo wp_json_encode($response);
    die();
  }

  public abstract function getData();
}
