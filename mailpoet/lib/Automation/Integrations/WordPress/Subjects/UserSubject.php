<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress\Subjects;

use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\WordPress\Payloads\UserPayload;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use WP_User;

/**
 * @implements Subject<UserPayload>
 */
class UserSubject implements Subject {
  const KEY = 'wordpress:user';

  public function getName(): string {
    return __('WordPress user', 'mailpoet');
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'user_id' => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $id = $subjectData->getArgs()['user_id'];
    $user = new WP_User($id);
    return new UserPayload($user);
  }

  public function getFields(): array {
    return [];
  }
}
