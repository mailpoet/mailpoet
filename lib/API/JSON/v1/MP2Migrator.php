<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

if(!defined('ABSPATH')) exit;

class MP2Migrator extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS
  );

  public function __construct() {
    $this->MP2Migrator = new \MailPoet\Config\MP2Migrator();
  }

  /**
   * Import end point
   *
   * @param object $data
   * @return object
   */
  public function import($data) {
    try {
      $process = $this->MP2Migrator->import($data);
      return $this->successResponse($process);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }

  /**
   * Stop import end point
   *
   * @param object $data
   * @return object
   */
  public function stopImport($data) {
    try {
      $process = $this->MP2Migrator->stopImport();
      return $this->successResponse($process);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }

  /**
   * Skip import end point
   *
   * @param object $data
   * @return object
   */
  public function skipImport($data) {
    try {
      $process = $this->MP2Migrator->skipImport();
      return $this->successResponse($process);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }

}
