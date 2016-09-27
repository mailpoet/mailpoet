<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

abstract class Endpoint {

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
        Error::UNKNOWN  => __('An unknown error occurred.', MAILPOET)
      );
    }
    return new ErrorResponse($errors, $meta, $status);
  }

  function badRequest($errors = array(), $meta = array()) {
    if(empty($errors)) {
      $errors = array(
        Error::BAD_REQUEST => __('Invalid request parameters.', MAILPOET)
      );
    }
    return new ErrorResponse($errors, $meta, Response::STATUS_BAD_REQUEST);
  }
}