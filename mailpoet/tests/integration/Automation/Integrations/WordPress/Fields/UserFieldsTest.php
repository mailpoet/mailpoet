<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WordPress\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WordPress\Payloads\UserPayload;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;
use WP_User;

class UserFieldsTest extends \MailPoetTest {
  public function testEmailField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $field = $fields['wordpress:user:email'];
    $this->assertSame('Email', $field->getName());
    $this->assertSame('string', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values (guest)
    $user = new WP_User();
    $user->ID = 0;
    $this->assertNull($field->getValue(new UserPayload($user)));

    // check values (registered)
    $user = new WP_User();
    $user->ID = 1;
    $user->user_email = 'test@exmaple.com'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->assertSame('test@exmaple.com', $field->getValue(new UserPayload($user)));
  }

  public function testIsGuestField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $field = $fields['wordpress:user:is-guest'];
    $this->assertSame('Is guest', $field->getName());
    $this->assertSame('boolean', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values (guest)
    $user = new WP_User();
    $user->ID = 0;
    $this->assertTrue($field->getValue(new UserPayload($user)));

    // check values (registered)
    $user = new WP_User();
    $user->ID = 1;
    $this->assertFalse($field->getValue(new UserPayload($user)));
  }

  public function testRegisteredDateField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $field = $fields['wordpress:user:registered-date'];
    $this->assertSame('Registered date', $field->getName());
    $this->assertSame('datetime', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values (guest)
    $user = new WP_User();
    $user->ID = 0;
    $this->assertNull($field->getValue(new UserPayload($user)));

    // check values (registered)
    $user = new WP_User();
    $user->ID = 1;
    $user->user_registered = '2023-06-01 14:03:27'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->assertEquals(new DateTimeImmutable('2023-06-01 14:03:27'), $field->getValue(new UserPayload($user)));
  }

  public function testRolesField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $field = $fields['wordpress:user:roles'];
    $this->assertSame('Roles', $field->getName());
    $this->assertSame('enum_array', $field->getType());
    $this->assertSame(['options' => $this->getAllRoles()], $field->getArgs());

    // check values (guest)
    $user = new WP_User();
    $user->ID = 0;
    $user->roles = [];
    $this->assertSame([], $field->getValue(new UserPayload($user)));

    // check values (registered)
    $user = new WP_User();
    $user->ID = 1;
    $user->roles = ['administrator', 'editor'];
    $this->assertSame(['administrator', 'editor'], $field->getValue(new UserPayload($user)));
  }

  private function getAllRoles(): array {
    global $wp_roles;
    $roles = [];
    foreach ($wp_roles->role_names as $id => $name) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $roles[] = ['id' => $id, 'name' => $name];
    }
    return $roles;
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(UserSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
