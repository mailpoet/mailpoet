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

  static function restore($orm) {
    $models = $orm->findResultSet();
    if(empty($models)) return false;

    $models->setExpr('deleted_at', 'NULL')->save();
    return $models->count();
  }

  static function trash($orm, $confirm = false) {
    $models = $orm->findResultSet();
    if(empty($models)) return false;

    if($confirm === true) {
      foreach($models as $model) {
        $model->delete();
      }
    } else {
      $models = $orm->findResultSet();
      $models->setExpr('deleted_at', 'NOW()')->save();
    }
    return $models->count();
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