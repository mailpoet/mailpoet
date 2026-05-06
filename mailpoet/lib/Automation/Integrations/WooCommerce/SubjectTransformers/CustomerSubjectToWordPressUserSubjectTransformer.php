<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\SubjectTransformers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;

class CustomerSubjectToWordPressUserSubjectTransformer implements SubjectTransformer {
  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  public function accepts(): string {
    return CustomerSubject::KEY;
  }

  public function returns(): string {
    return UserSubject::KEY;
  }

  public function transform(Subject $data): ?Subject {
    if ($this->accepts() !== $data->getKey()) {
      throw new \InvalidArgumentException('Invalid subject type');
    }

    $customerId = (int)($data->getArgs()['customer_id'] ?? 0);
    if ($customerId <= 0) {
      return null;
    }

    $user = $this->wordPress->getUserBy('id', $customerId);
    if (!$user instanceof \WP_User || !$user->exists()) {
      return null;
    }

    return new Subject(UserSubject::KEY, ['user_id' => $customerId]);
  }
}
