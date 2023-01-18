<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

class TrackerTest extends \MailPoetTest {
  public function testItAddsTrackingData() {
    $tracker = $this->diContainer->get(Tracker::class);
    $data = $tracker->addTrackingData(['extensions' => []]);
    expect($data['extensions']['mailpoet'])->notEmpty();
  }
}
