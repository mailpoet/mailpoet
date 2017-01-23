<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Form extends Model {
  public static $_table = MP_FORMS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
  }

  function asArray() {
    $model = parent::asArray();

    $model['body'] = (is_serialized($this->body))
      ? unserialize($this->body)
      : $this->body;
    $model['settings'] = (is_serialized($this->settings))
      ? unserialize($this->settings)
      : $this->settings;

    return $model;
  }

  function save() {
    $this->set('body', (is_serialized($this->body))
      ? $this->body
      : serialize($this->body)
    );
    $this->set('settings', (is_serialized($this->settings))
      ? $this->settings
      : serialize($this->settings)
    );
    return parent::save();
  }

  function getFieldList() {
    $form = $this->asArray();
    if(empty($form['body'])) {
      return false;
    }

    $skipped_types = array('html', 'divider', 'submit');
    $fields = array();

    foreach((array)$form['body'] as $field) {
      if(empty($field['id'])
        || empty($field['type'])
        || in_array($field['type'], $skipped_types)
      ) {
        continue;
      }
      if($field['id'] > 0) {
        $fields[] = 'cf_' . $field['id'];
      } else {
        $fields[] = $field['id'];
      }
    }

    return $fields ?: false;
  }

  function filterSegments(array $segment_ids = array()) {
    $form = $this->asArray();
    if(empty($form['settings']['segments'])) {
      return array();
    }

    if(!empty($form['settings']['segments_selected_by'])
      && $form['settings']['segments_selected_by'] == 'user'
    ) {
      $segment_ids = array_intersect($segment_ids, $form['settings']['segments']);
    } else {
      $segment_ids = $form['settings']['segments'];
    }

    return $segment_ids;
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => Form::getPublished()->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => Form::getTrashed()->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    }
    return $orm->whereNull('deleted_at');
  }

  static function createOrUpdate($data = array()) {
    $form = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $form = self::findOne((int)$data['id']);
    }

    if($form === false) {
      $form = self::create();
      $form->hydrate($data);
    } else {
      unset($data['id']);
      $form->set($data);
    }

    return $form->save();
  }
}
