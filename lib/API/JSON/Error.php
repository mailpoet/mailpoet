<?php
namespace MailPoet\API\JSON;

if (!defined('ABSPATH')) exit;

final class Error {
  const UNKNOWN = 'unknown';
  const BAD_REQUEST = 'bad_request';
  const UNAUTHORIZED = 'unauthorized';
  const FORBIDDEN = 'forbidden';
  const NOT_FOUND = 'not_found';

  private function __construct() {

  }
}
