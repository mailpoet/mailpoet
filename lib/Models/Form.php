<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

/**
 * @property string|array $settings
 * @property string|array $body
 */
class Form extends Model {
  public static $_table = MP_FORMS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
  }

  function getSettings() {
    return is_serialized($this->settings) ? unserialize($this->settings) : $this->settings;
  }

  function getBody() {
    return is_serialized($this->body) ? unserialize($this->body) : $this->body;
  }

  function asArray() {
    $model = parent::asArray();

    $model['body'] = $this->getBody();
    $model['settings'] = $this->getSettings();

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
    $body = $this->getBody();
    if(empty($body)) {
      return false;
    }

    $skipped_types = array('html', 'divider', 'submit');
    $fields = array();

    foreach((array)$body as $field) {
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
    $settings = $this->getSettings();
    if(empty($settings['segments'])) {
      return array();
    }

    if(!empty($settings['segments_selected_by'])
      && $settings['segments_selected_by'] == 'user'
    ) {
      $segment_ids = array_intersect($segment_ids, $settings['segments']);
    } else {
      $segment_ids = $settings['segments'];
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

}
