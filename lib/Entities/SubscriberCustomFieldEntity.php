<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="subscriber_custom_field")
 */
class SubscriberCustomFieldEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SubscriberEntity")
   * @var SubscriberEntity|null
   */
  private $subscriber;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\CustomFieldEntity")
   * @var CustomFieldEntity|null
   */
  private $customField;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $value;

  public function __construct(
    SubscriberEntity $subscriber,
    CustomFieldEntity $customField,
    string $value
  ) {
    $this->subscriber = $subscriber;
    $this->customField = $customField;
    $this->value = $value;
  }

  /**
   * @return SubscriberEntity|null
   */
  public function getSubscriber() {
    $this->safelyLoadToOneAssociation('subscriber');
    return $this->subscriber;
  }

  public function getValue(): string {
    return $this->value;
  }

  /**
   * @return CustomFieldEntity|null
   */
  public function getCustomField() {
    return $this->customField;
  }
}
