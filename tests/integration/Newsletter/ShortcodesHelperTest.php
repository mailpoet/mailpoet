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
    $customField = CustomField::create();
    $customField->name = 'name';
    $customField->type = 'type';
    $customField->save();
    $shortcodes = ShortcodesHelper::getShortcodes();
    expect(count($shortcodes['Subscriber']))->equals(6);
    $customSubscriberShortcode = end($shortcodes['Subscriber']);
    expect($customSubscriberShortcode['text'])->equals($customField->name);
    expect($customSubscriberShortcode['shortcode'])
      ->equals('[subscriber:cf_' . $customField->id . ']');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
  }
}
