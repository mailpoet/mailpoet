<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberTest extends \MailPoetTest {
  private Subscriber $subscriber;
  private WPFunctions $wp;

  public function _before() {
    parent::_before();
    $this->subscriber = $this->diContainer->get(Subscriber::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItReturnsDisplayNameForWordPressUser(): void {
    wp_create_user('mailpoet_display_name_user', 'pass', 'wp-user@example.com');
    $wpUser = get_user_by('login', 'mailpoet_display_name_user');
    $this->assertInstanceOf(\WP_User::class, $wpUser);

    $subscriber = (new SubscriberFactory())
      ->withEmail('subscriber-with-wp-user@example.com')
      ->withWpUserId((int)$wpUser->ID)
      ->create();

    $result = $this->subscriber->getDisplayName(
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'member']
    );

    $this->assertSame($wpUser->display_name, $result); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItReturnsDefaultDisplayNameWhenSubscriberIsNotWordPressUser(): void {
    $subscriber = (new SubscriberFactory())->create();

    $result = $this->subscriber->getDisplayName(
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'member']
    );

    $this->assertSame('member', $result);
  }

  public function testItReturnsDefaultDisplayNameWhenSubscriberIsMissing(): void {
    $result = $this->subscriber->getDisplayName(
      ['recipient_email' => 'missing@example.com'],
      ['default' => 'member']
    );

    $this->assertSame('member', $result);
  }

  public function testItReturnsSubscribersCount(): void {
    (new SubscriberFactory())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new SubscriberFactory())->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();

    $expectedCount = $this->diContainer->get(SubscribersRepository::class)->countBy([
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'deletedAt' => null,
    ]);

    $result = $this->subscriber->getCount([]);

    $this->assertSame((string)$expectedCount, $result);
  }

  public function testItReturnsCustomFieldValue(): void {
    $subscriber = (new SubscriberFactory())->create();
    $customField = (new CustomFieldFactory())
      ->withType(CustomFieldEntity::TYPE_TEXT)
      ->withSubscriber($subscriber->getId(), 'Custom value')
      ->create();

    $result = $this->subscriber->getCustomField(
      (int)$customField->getId(),
      ['recipient_email' => $subscriber->getEmail()]
    );

    $this->assertSame('Custom value', $result);
  }

  public function testItReturnsDefaultWhenCustomFieldValueIsEmpty(): void {
    $subscriber = (new SubscriberFactory())->create();
    $customField = (new CustomFieldFactory())
      ->withType(CustomFieldEntity::TYPE_TEXT)
      ->create();

    $result = $this->subscriber->getCustomField(
      (int)$customField->getId(),
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'fallback']
    );

    $this->assertSame('fallback', $result);
  }

  public function testItFormatsDateCustomFieldValue(): void {
    $subscriber = (new SubscriberFactory())->create();
    $customField = (new CustomFieldFactory())
      ->withType(CustomFieldEntity::TYPE_DATE)
      ->withSubscriber($subscriber->getId(), '2010-04-20 00:00:00')
      ->create();

    $result = $this->subscriber->getCustomField(
      (int)$customField->getId(),
      ['recipient_email' => $subscriber->getEmail()],
      ['format' => 'F j']
    );

    $expected = $this->wp->dateI18n('F j', (int)strtotime('2010-04-20 00:00:00'));
    $this->assertSame($expected, $result);
  }

  public function testItEncodesFirstNameSpecialCharacters(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('encode-first@example.com')
      ->withFirstName('<script>alert(1)</script>')
      ->create();

    $result = $this->subscriber->getFirstName(
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'member']
    );

    $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
  }

  public function testItEncodesLastNameSpecialCharacters(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('encode-last@example.com')
      ->withLastName('"><b>x</b>')
      ->create();

    $result = $this->subscriber->getLastName(
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'member']
    );

    $this->assertSame('&quot;&gt;&lt;b&gt;x&lt;/b&gt;', $result);
  }

  public function testItEncodesEmailSpecialCharacters(): void {
    $result = $this->subscriber->getEmail(
      ['recipient_email' => '"><b>@example.com'],
      []
    );

    $this->assertSame('&quot;&gt;&lt;b&gt;@example.com', $result);
  }

  public function testItEncodesDisplayNameSpecialCharacters(): void {
    wp_create_user('mailpoet_encode_display', 'pass', 'encode-display@example.com');
    $wpUser = get_user_by('login', 'mailpoet_encode_display');
    $this->assertInstanceOf(\WP_User::class, $wpUser);
    wp_update_user(['ID' => $wpUser->ID, 'display_name' => 'Boss & Co']);

    $subscriber = (new SubscriberFactory())
      ->withEmail('sub-encode-display@example.com')
      ->withWpUserId((int)$wpUser->ID)
      ->create();

    $result = $this->subscriber->getDisplayName(
      ['recipient_email' => $subscriber->getEmail()],
      ['default' => 'member']
    );

    $this->assertSame('Boss &amp; Co', $result);
  }

  public function testItEncodesTheDefaultFallbackValue(): void {
    $result = $this->subscriber->getFirstName(
      ['recipient_email' => 'no-such-subscriber@example.com'],
      ['default' => 'Tom & Jerry']
    );

    $this->assertSame('Tom &amp; Jerry', $result);
  }
}
