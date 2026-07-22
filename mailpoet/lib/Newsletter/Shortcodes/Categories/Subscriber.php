<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class Subscriber implements CategoryInterface {

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SubscriberCustomFieldRepository $subscriberCustomFieldRepository,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberCustomFieldRepository = $subscriberCustomFieldRepository;
    $this->wp = $wp;
  }

  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    if (!($subscriber instanceof SubscriberEntity)) {
      return $shortcodeDetails['shortcode'];
    }
    $defaultValue = ($shortcodeDetails['action_argument'] === 'default') ?
      $shortcodeDetails['action_argument_value'] :
      '';
    switch ($shortcodeDetails['action']) {
      case 'firstname':
        return htmlspecialchars((!empty($subscriber->getFirstName())) ? $subscriber->getFirstName() : $defaultValue, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
      case 'lastname':
        return htmlspecialchars(!empty($subscriber->getLastName()) ? $subscriber->getLastName() : $defaultValue, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
      case 'email':
        return htmlspecialchars((string)$subscriber->getEmail(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
      case 'displayname':
        if ($subscriber->getWpUserId()) {
          $wpUser = WPFunctions::get()->getUserdata($subscriber->getWpUserId());
          return (string)$wpUser->display_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        }
        return htmlspecialchars($defaultValue, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
      case 'count':
        return (string)$this->getSubscribersCountWithSubscribedStatus();
      default:
        if (
          preg_match('/cf_(\d+)/', $shortcodeDetails['action'], $customField) &&
          !empty($subscriber->getId())
        ) {
          $customField = $this->subscriberCustomFieldRepository->findOneBy([
            'subscriber' => $subscriber,
            'customField' => $customField[1],
          ]);
          if (!($customField instanceof SubscriberCustomFieldEntity) || empty($customField->getValue())) {
            return $defaultValue;
          }
          $customFieldDefinition = $customField->getCustomField();
          if (
            $shortcodeDetails['action_argument'] === 'format'
            && $customFieldDefinition instanceof CustomFieldEntity
            && $customFieldDefinition->getType() === CustomFieldEntity::TYPE_DATE
          ) {
            $timestamp = strtotime($customField->getValue());
            if ($timestamp !== false) {
              return $this->wp->dateI18n($shortcodeDetails['action_argument_value'], $timestamp);
            }
            return $defaultValue;
          }
          if (
            $customFieldDefinition instanceof CustomFieldEntity &&
            $customFieldDefinition->getType() === CustomFieldEntity::TYPE_CHECKBOX &&
            $customField->getValue() === '1'
          ) {
            $params = $customFieldDefinition->getParams();
            $label = (is_array($params) && isset($params['values'][0]['value'])) ? (string)$params['values'][0]['value'] : '';
            return $label !== '' ? htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : $defaultValue;
          }
          return htmlspecialchars($customField->getValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        }
        return null;
    }
  }

  private function getSubscribersCountWithSubscribedStatus(): int {
    return $this->subscribersRepository->countBy(['status' => SubscriberEntity::STATUS_SUBSCRIBED, 'deletedAt' => null]);
  }
}
