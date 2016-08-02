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

    return new ErrorResponse($errors, $meta, $status);
  }

  function badRequest($errors = array(), $meta = array()) {
    return new ErrorResponse($errors, $meta, Response::STATUS_BAD_REQUEST);
  }
}