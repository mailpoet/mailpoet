<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\SubjectTransformers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;

class OrderSubjectToWordPressUserSubjectTransformer implements SubjectTransformer {
  /** @var WooCommerce */
  private $wooCommerce;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WooCommerce $wooCommerce,
    WordPress $wordPress
  ) {
    $this->wooCommerce = $wooCommerce;
    $this->wordPress = $wordPress;
  }

  public function accepts(): string {
    return OrderSubject::KEY;
  }

  public function returns(): string {
    return UserSubject::KEY;
  }

  public function transform(Subject $data): ?Subject {
    if ($this->accepts() !== $data->getKey()) {
      throw new \InvalidArgumentException('Invalid subject type');
    }

    $orderId = (int)($data->getArgs()['order_id'] ?? 0);
    if ($orderId <= 0) {
      return null;
    }

    $order = $this->wooCommerce->wcGetOrder($orderId);
    if (!$order instanceof \WC_Order) {
      return null;
    }

    $userId = $order->get_user_id();
    if ($userId <= 0) {
      return null;
    }

    $user = $this->wordPress->getUserBy('id', $userId);
    if (!$user instanceof \WP_User || !$user->exists()) {
      return null;
    }

    return new Subject(UserSubject::KEY, ['user_id' => $userId]);
  }
}
