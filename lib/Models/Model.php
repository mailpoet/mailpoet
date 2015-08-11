<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) {
  exit;
}

class Model extends \Sudzy\ValidModel {
  function __construct() {
    $customValidator = new CustomValidator();
    parent::__construct($customValidator->init());
  }
}