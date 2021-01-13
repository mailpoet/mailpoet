<?php

namespace MailPoet\Models;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property string|array $settings
 * @property string|array $body
 * @property string $name
 * @property string $status
 * @property string|null $deletedAt
 */

class Form extends Model {
  public static $_table = MP_FORMS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public function getSettings() {
    if (is_array($this->settings) || $this->settings === null) {
      return $this->settings;
    }
    return WPFunctions::get()->isSerialized($this->settings) ? unserialize($this->settings) : $this->settings;
  }

  public function getBody() {
    if (is_array($this->body) || $this->body === null) {
      return $this->body;
    }
    return WPFunctions::get()->isSerialized($this->body) ? unserialize($this->body) : $this->body;
  }

  public function asArray() {
    $model = parent::asArray();

    $model['body'] = $this->getBody();
    $model['settings'] = $this->getSettings();

    return $model;
  }

  public function save() {
    $this->set('body', (is_string($this->body) && is_serialized($this->body))
      ? $this->body
      : serialize($this->body)
    );
    $this->set('settings', (is_string($this->settings) && is_serialized($this->settings))
      ? $this->settings
      : serialize($this->settings)
    );
    return parent::save();
  }

  public function getFieldList(array $body = null) {
    $body = $body ?? $this->getBody();
    if (empty($body)) {
      return false;
    }

    $skippedTypes = ['html', 'divider', 'submit'];
    $nestedTypes = ['column', 'columns'];
    $fields = [];

    foreach ((array)$body as $field) {
      if (!empty($field['type'])
        && in_array($field['type'], $nestedTypes)
        && !empty($field['body'])
      ) {
        $nestedFields = $this->getFieldList($field['body']);
        if ($nestedFields) {
          $fields = array_merge($fields, $nestedFields);
        }
        continue;
      }

      if (empty($field['id'])
        || empty($field['type'])
        || in_array($field['type'], $skippedTypes)
      ) {
        continue;
      }

      if ((int)$field['id'] > 0) {
        $fields[] = 'cf_' . $field['id'];
      } else {
        $fields[] = $field['id'];
      }
    }

    return $fields ?: false;
  }

  public static function search($orm, $search = '') {
    return $orm->whereLike('name', '%' . $search . '%');
  }

  public static function groups() {
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

  public static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    }
    return $orm->whereNull('deleted_at');
  }

  public static function getDefaultSuccessMessage() {
    $settings = SettingsController::getInstance();
    if ($settings->get('signup_confirmation.enabled')) {
      return __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet');
    }
    return __('You’ve been successfully subscribed to our newsletter!', 'mailpoet');
  }

  public static function updateSuccessMessages() {
    $rightMessage = self::getDefaultSuccessMessage();
    $wrongMessage = (
      $rightMessage === __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')
      ? __('You’ve been successfully subscribed to our newsletter!', 'mailpoet')
      : __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')
    );
    $forms = self::findMany();
    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (isset($settings['success_message']) && $settings['success_message'] === $wrongMessage) {
        $settings['success_message'] = $rightMessage;
        $form->set('settings', serialize($settings));
        $form->save();
      }
    }
  }
}
