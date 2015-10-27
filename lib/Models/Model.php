<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

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
    if($this->created_at === null) {
      $this->set_expr('created_at', 'NOW()');
    }
  }

  static function filterSearchCustomFields($orm, $searchCriteria = array(), $searchCondition = 'AND', $searchSymbol = '=') {
    $havingFields = array_filter(
      array_map(function ($customField) use ($searchSymbol) {
        return sprintf('`%s` %s ?', $customField['name'], $searchSymbol);
      }, $searchCriteria)
    );
    $havingValues = array_map(function ($customField) use ($searchSymbol) {
      return (strtolower($searchSymbol) === 'like') ? '%' . $customField['value'] . '%' : $customField['value'];
    }, $searchCriteria);
    return $orm->having_raw(implode(' ' . $searchCondition . ' ', $havingFields), array_values($havingValues));
  }
}