<?php

class SettingsCest
{
  public function _before() {
    $this->settings = \MailPoet\Settings::getAll();
  }

  public function it_has_defaults() {
    $settings = \MailPoet\Settings::getDefaults();
    expect($this->settings)->notEmpty();
  }

  public function it_should_load_default_settings(UnitTester $I) {
    $settings = \MailPoet\Settings::getAll();
    $defaults = \MailPoet\Settings::getDefaults();
    expect($settings)->equals($defaults);
  }

  public function it_should_update_settings() {
    $new_settings = array('test_key' => true);
    \MailPoet\Settings::save($new_settings);

    $settings = \MailPoet\Settings::getAll();

    expect_that(isset($settings['test_key']) && $settings['test_key'] === true);
  }

  public function it_should_reset_settings() {
    $settings = \MailPoet\Settings::getAll();

    \MailPoet\Settings::clearAll();

    $reset_settings = \MailPoet\Settings::getAll();

    expect($settings)->notEmpty();
    expect($reset_settings)->equals(\MailPoet\Settings::getDefaults());
  }
}

function get_option($value, $default) {
  return $default;
}
function add_option() {
  return true;
}
function update_option() {
  return true;
}
function delete_option() {
  return true;
}