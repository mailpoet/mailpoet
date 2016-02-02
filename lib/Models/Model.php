<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Model extends \Sudzy\ValidModel {
  function __construct() {
    $customValidators = new CustomValidator();
    parent::__construct($customValidators->init());
  }

  static function create() {
    return parent::create();
  }

  function save() {
    $this->setTimestamp();
    try {
      parent::save();
      return true;
    } catch (\Sudzy\ValidationException $e) {
      return array_unique($e->getValidationErrors());
    } catch (\PDOException $e) {
      return array($e->getMessage());
    }
    return false;
  }

  function trash() {
    return $this->set_expr('deleted_at', 'NOW()')->save();
  }

  static function bulkTrash($orm) {
    $models = $orm->findResultSet();
    $models->set_expr('deleted_at', 'NOW()')->save();
    return $models->count();
  }

  static function bulkDelete($orm) {
    $models = $orm->findMany();
    $count = 0;
    foreach($models as $model) {
      $model->delete();
      $count++;
    }
    return $count;
  }

  function restore() {
    return $this->set_expr('deleted_at', 'NULl')->save();
  }

  static function bulkRestore($orm) {
    $models = $orm->findResultSet();
    $models->set_expr('deleted_at', 'NULL')->save();
    return $models->count();
  }

  function duplicate($data = array()) {
    $model = get_called_class();
    $model_data = array_merge($this->asArray(), $data);
    unset($model_data['id']);

    $duplicate =  $model::create();
    $duplicate->hydrate($model_data);
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    $duplicate->set_expr('deleted_at', 'NULL');

    if($duplicate->save()) {
      return $duplicate;
    } else {
      return false;
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

  static function getPublished() {
    return static::whereNull('deleted_at');
  }

  static function getTrashed() {
    return static::whereNotNull('deleted_at');
  }
}