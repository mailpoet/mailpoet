<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class Subscriber {

  private const HTML_ENTITY_FLAGS = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401;

  private SubscribersRepository $subscribersRepository;
  private SubscriberCustomFieldRepository $subscriberCustomFieldRepository;
  private WPFunctions $wp;
  private SubscriptionUrlFactory $subscriptionUrlFactory;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SubscriberCustomFieldRepository $subscriberCustomFieldRepository,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberCustomFieldRepository = $subscriberCustomFieldRepository;
    $this->wp = $wp;
    $this->subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
  }

  public function getFirstName(array $context, array $args = []): string {
    $subscriber = $this->getSubscriber($context);
    $value = ($subscriber && $subscriber->getFirstName()) ? $subscriber->getFirstName() : ($args['default'] ?? '');

    return htmlspecialchars($value, self::HTML_ENTITY_FLAGS);
  }

  public function getLastName(array $context, array $args = []): string {
    $subscriber = $this->getSubscriber($context);
    $value = ($subscriber && $subscriber->getLastName()) ? $subscriber->getLastName() : ($args['default'] ?? '');

    return htmlspecialchars($value, self::HTML_ENTITY_FLAGS);
  }

  public function getEmail(array $context, array $args = []): string {
    return htmlspecialchars($context['recipient_email'] ?? '', self::HTML_ENTITY_FLAGS);
  }

  public function getActivationLink(array $context, array $args = []): string {
    $subscriber = $this->getSubscriber($context);

    if (!$subscriber) {
      return '';
    }

    return $this->subscriptionUrlFactory->getConfirmationUrl($subscriber);
  }

  public function getDisplayName(array $context, array $args = []): string {
    $default = $args['default'] ?? '';
    $subscriber = $this->getSubscriber($context);
    if (!$subscriber || !$subscriber->getWpUserId()) {
      return htmlspecialchars($default, self::HTML_ENTITY_FLAGS);
    }

    $wpUser = $this->wp->getUserdata($subscriber->getWpUserId());

    return ($wpUser instanceof \WP_User) ? (string)$wpUser->display_name : htmlspecialchars($default, self::HTML_ENTITY_FLAGS); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function getCount(array $context, array $args = []): string {
    return (string)$this->subscribersRepository->countBy([
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'deletedAt' => null,
    ]);
  }

  public function getCustomField(int $customFieldId, array $context, array $args = []): string {
    $default = $args['default'] ?? '';
    $subscriber = $this->getSubscriber($context);
    if (!$subscriber || !$subscriber->getId()) {
      return $default;
    }

    $subscriberCustomField = $this->subscriberCustomFieldRepository->findOneBy([
      'subscriber' => $subscriber,
      'customField' => $customFieldId,
    ]);
    if (!($subscriberCustomField instanceof SubscriberCustomFieldEntity) || empty($subscriberCustomField->getValue())) {
      return $default;
    }

    $value = (string)$subscriberCustomField->getValue();
    $definition = $subscriberCustomField->getCustomField();
    $format = $args['format'] ?? '';

    if ($format !== '' && $definition instanceof CustomFieldEntity && $definition->getType() === CustomFieldEntity::TYPE_DATE) {
      $timestamp = strtotime($value);

      return $timestamp !== false ? $this->wp->dateI18n($format, $timestamp) : $default;
    }

    if ($definition instanceof CustomFieldEntity && $definition->getType() === CustomFieldEntity::TYPE_CHECKBOX && $value === '1') {
      $params = $definition->getParams();
      $label = (is_array($params) && isset($params['values'][0]['value'])) ? (string)$params['values'][0]['value'] : '';

      return $label !== '' ? htmlspecialchars($label, self::HTML_ENTITY_FLAGS) : $default;
    }

    return htmlspecialchars($value, self::HTML_ENTITY_FLAGS);
  }

  private function getSubscriber(array $context): ?SubscriberEntity {
    $subscriberEmail = $context['recipient_email'] ?? null;

    return $subscriberEmail ? $this->subscribersRepository->findOneBy(['email' => $subscriberEmail]) : null;
  }
}
