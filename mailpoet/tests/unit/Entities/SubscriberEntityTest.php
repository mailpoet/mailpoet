<?php declare(strict_types = 1);

namespace MailPoet\Entities;

class SubscriberEntityTest extends \MailPoetUnitTest {
  public function testMagicGetterReturnsData() {
    $subscriber = new SubscriberEntity();
    $subscriber->setWpUserId(4);
    verify($subscriber->wp_user_id)->equals(4);// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testMagicGetterThrowsDeprecationWarning() {
    $expectedMessage = 'Direct access to $subscriber->wp_user_id is deprecated and will be removed after 2026-01-01. Use $subscriber->getWpUserId() instead.';
    $caughtError = null;
    set_error_handler(function ($errno, $errstr) use (&$caughtError) {
      if ($errno === E_USER_DEPRECATED) {
        $caughtError = $errstr;
      }
    });
    $subscriber = new SubscriberEntity();
    $subscriber->setWpUserId(4);
    $subscriber->wp_user_id;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    restore_error_handler();
    verify($caughtError)->equals($expectedMessage);
  }

  public function testMagicGetterReturnsNullForUnknown() {
    $subscriber = new SubscriberEntity();
    verify($subscriber->non_existing_property)->null();// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
