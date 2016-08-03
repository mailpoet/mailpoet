<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

final class Error {
  const UNKNOWN = 'unknown';
  const BAD_REQUEST = 'bad_request';
  const UNAUTHORIZED = 'unauthorized';
  const FORBIDDEN = 'forbidden';

  private function __construct() {

  }
}