<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;

class ShortcodesHelperTest extends \MailPoetTest {
  /** @var ShortcodesHelper */
  private $shortcodesHelper;

  public function _before() {
    $this->shortcodesHelper = $this->diContainer->get(ShortcodesHelper::class);
  }

  public function testGetsShortcodes() {
    $shortcodes = $this->shortcodesHelper->getShortcodes();
    expect(array_keys($shortcodes))->equals(
      [
        'Subscriber',
        'Newsletter',
        'Post Notifications',
        'Date',
        'Links',
        'Site',
      ]
    );
  }

  public function testItGetsCustomShortShortcodes() {
    $shortcodes = $this->shortcodesHelper->getShortcodes();
    expect(count($shortcodes['Subscriber']))->equals(5);
    $customField = new CustomFieldEntity();
    $customField->setName('name');
    $customField->setType('type');
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $shortcodes = $this->shortcodesHelper->getShortcodes();
    expect(count($shortcodes['Subscriber']))->equals(6);
    $customSubscriberShortcode = end($shortcodes['Subscriber']);
    expect($customSubscriberShortcode['text'])->equals($customField->getName());
    expect($customSubscriberShortcode['shortcode'])
      ->equals('[subscriber:cf_' . $customField->getId() . ']');
  }
}
