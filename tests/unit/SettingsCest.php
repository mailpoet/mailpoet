<?php
use \UnitTester;
use \Codeception\Util\Stub;
use \MailPoet\Config\Settings;

class SettingsCest {

    public function _before() {
      $stub_options = Stub::make('\MailPoet\WP\Option', [
        'get' => 'value',
        'set' => true
      ]);
      $this->settings = new Settings();
      $this->settings->options = $stub_options;
    }

    public function itCanSaveSettings() {
      $saved = $this->settings->save('key', 'value');
      expect($saved)->equals(true);
    }

    public function itCanLoadSettings() {
      $this->settings->save('key', 'value');
      $value = $this->settings->load('key');
      expect($value)->equals('value');
    }

    public function _after() {
    }
}
