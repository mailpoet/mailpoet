<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Model extends \Sudzy\ValidModel {
  function __construct() {
    $customValidators = new CustomValidator();
    parent::__construct($customValidators->init());
  }

  function save() {
    $this->setTimestamp();

    try {
      parent::save();
      return true;
    } catch (\Sudzy\ValidationException $e) {
      return array_unique($e->getValidationErrors());
    } catch (\PDOException $e) {
      return $e->getMessage();
    }
  }

  private function setTimestamp() {
    if ($this->created_at === null) {
      $this->created_at = date('Y-m-d H:i:s');
    }
  }
}
