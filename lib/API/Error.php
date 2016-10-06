<?php
if(!defined('ABSPATH')) exit;

namespace MailPoet\API;

final class Error {
  const UNKNOWN = 'unknown';
  const BAD_REQUEST = 'bad_request';
  const UNAUTHORIZED = 'unauthorized';
  const FORBIDDEN = 'forbidden';
  const NOT_FOUND = 'not_found';

  private function __construct() {

  }
}