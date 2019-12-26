<?php

namespace MailPoet\Test\Newsletter;

use MailPoet\Models\CustomField;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoetVendor\Idiorm\ORM;

class ShortcodesHelperTest extends \MailPoetTest {
  public function testGetsShortcodes() {
    $shortcodes = ShortcodesHelper::getShortcodes();
    expect(array_keys($shortcodes))->equals(
      [
        'Subscriber',
        'Newsletter',
        'Post Notifications',
        'Date',
        'Links',
      ]
    );
  }

  public function testItGetsCustomShortShortcodes() {
    $shortcodes = ShortcodesHelper::getShortcodes();
    expect(count($shortcodes['Subscriber']))->equals(5);
    $custom_field = CustomField::create();
    $custom_field->name = 'name';
    $custom_field->type = 'type';
    $custom_field->save();
    $shortcodes = ShortcodesHelper::getShortcodes();
    expect(count($shortcodes['Subscriber']))->equals(6);
    $custom_subscriber_shortcode = end($shortcodes['Subscriber']);
    expect($custom_subscriber_shortcode['text'])->equals($custom_field->name);
    expect($custom_subscriber_shortcode['shortcode'])
      ->equals('[subscriber:cf_' . $custom_field->id . ']');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
  }
}
