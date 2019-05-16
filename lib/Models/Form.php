<?php
namespace MailPoet\Models;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property string|array $settings
 * @property string|array $body
 * @property string $name
 */

class Form extends Model {
  public static $_table = MP_FORMS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => __('Please specify a name.', 'mailpoet'),
    ]);
  }

  function getSettings() {
    return WPFunctions::get()->isSerialized($this->settings) ? unserialize($this->settings) : $this->settings;
  }

  function getBody() {
    return WPFunctions::get()->isSerialized($this->body) ? unserialize($this->body) : $this->body;
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
    if (empty($body)) {
      return false;
    }

    $skipped_types = ['html', 'divider', 'submit'];
    $fields = [];

    foreach ((array)$body as $field) {
      if (empty($field['id'])
        || empty($field['type'])
        || in_array($field['type'], $skipped_types)
      ) {
        continue;
      }
      if ($field['id'] > 0) {
        $fields[] = 'cf_' . $field['id'];
      } else {
        $fields[] = $field['id'];
      }
    }

    return $fields ?: false;
  }

  function filterSegments(array $segment_ids = []) {
    $settings = $this->getSettings();
    if (empty($settings['segments'])) {
      return [];
    }

    if (!empty($settings['segments_selected_by'])
      && $settings['segments_selected_by'] == 'user'
    ) {
      $segment_ids = array_intersect($segment_ids, $settings['segments']);
    } else {
      $segment_ids = $settings['segments'];
    }

    return $segment_ids;
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%' . $search . '%');
  }

  static function groups() {
    return [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => Form::getPublished()->count(),
      ],
      [
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => Form::getTrashed()->count(),
      ],
    ];
  }

  static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    }
    return $orm->whereNull('deleted_at');
  }

  static function getDefaultSuccessMessage() {
    $settings = new SettingsController;
    if ($settings->get('signup_confirmation.enabled')) {
      return __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet');
    }
    return __('You’ve been successfully subscribed to our newsletter!', 'mailpoet');
  }

  static function updateSuccessMessages() {
    $right_message = self::getDefaultSuccessMessage();
    $wrong_message = (
      $right_message === __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')
      ? __('You’ve been successfully subscribed to our newsletter!', 'mailpoet')
      : __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')
    );
    $forms = self::findMany();
    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (isset($settings['success_message']) && $settings['success_message'] === $wrong_message) {
        $settings['success_message'] = $right_message;
        $form->set('settings', serialize($settings));
        $form->save();
      }
    }
  }

}