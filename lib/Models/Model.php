<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Model extends \Sudzy\ValidModel {
  protected $_errors;

  function __construct() {
    $this->_errors = array();
    parent::__construct();
  }

  static function create() {
    return parent::create();
  }

  function getErrors() {
    if(empty($this->_errors)) {
      return false;
    } else {
      return $this->_errors;
    }
  }

  function setError($error = '') {
    if(!empty($error)) {
      if(is_array($error)) {
        $this->_errors = array_merge($this->_errors, $error);
        $this->_errors = array_unique($this->_errors);
      } else {
        $this->_errors[] = $error;
      }
    }
  }

  function save() {
    $this->setTimestamp();
    try {
      parent::save();
    } catch(\Sudzy\ValidationException $e) {
      $this->setError($e->getValidationErrors());
    } catch(\PDOException $e) {
      $this->setError($e->getMessage());
    } catch(\Exception $e) {
      $this->setError($e->getMessage());
    }
    return $this;
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