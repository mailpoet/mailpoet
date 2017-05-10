<?php
namespace MailPoet\API\JSON;

if(!defined('ABSPATH')) exit;

abstract class Endpoint {

  public $permissions = array();

  function successResponse(
    $data = array(), $meta = array(), $status = Response::STATUS_OK
  ) {
    return new SuccessResponse($data, $meta, $status);
  }

  function errorResponse(
    $errors = array(), $meta = array(), $status = Response::STATUS_NOT_FOUND
  ) {
    if(empty($errors)) {
      $errors = array(
        Error::UNKNOWN  => __('An unknown error occurred.', 'mailpoet')
      );
    }
    return new ErrorResponse($errors, $meta, $status);
  }

  function badRequest($errors = array(), $meta = array()) {
    if(empty($errors)) {
      $errors = array(
        Error::BAD_REQUEST => __('Invalid request parameters', 'mailpoet')
      );
    }
    return new ErrorResponse($errors, $meta, Response::STATUS_BAD_REQUEST);
  }
}
