<?php

namespace MailPoet\Subscription;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\WP\Functions as WPFunctions;

class ManageSubscriptionFormRendererTest extends \MailPoetTest {
  /** @var ManageSubscriptionFormRenderer */
  private $formRenderer;

  public function _before() {
    $this->cleanup();
    $this->formRenderer = $this->diContainer->get(ManageSubscriptionFormRenderer::class);
    parent::_before();
  }

  public function testItGeneratesForm() {
    $subscriber = $this->getSubscriber($this->getSegment());
    $form = $this->formRenderer->renderForm($subscriber);
    expect($form)->regExp('/<form class="mailpoet-manage-subscription" method="post" action="[a-z0-9:\/\._]+wp-admin\/admin-post.php" novalidate>/');
    expect($form)->stringContainsString('<input type="hidden" name="data[email]" value="subscriber@test.com" />');
    expect($form)->regExp('/<input type="text" autocomplete="given-name" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="First name" value="Fname" data-automation-id="form_first_name" data-parsley-names=\'\[&quot;Please specify a valid name.&quot;,&quot;Addresses in names are not permitted, please add your name instead\.&quot;\]\'\/>/');
    expect($form)->regExp('/<input type="text" autocomplete="family-name" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Last name" value="Lname" data-automation-id="form_last_name" data-parsley-names=\'\[&quot;Please specify a valid name.&quot;,&quot;Addresses in names are not permitted, please add your name instead\.&quot;\]\'\/>/');
    expect($form)->regExp('/<input type="checkbox" class="mailpoet_checkbox" name="data\[[a-zA-Z0-9=_]+\]\[\]" value="1" checked="checked"  \/> Test segment/');
    expect($form)->regExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="custom field 1" value="some value"  \/>/');
    expect($form)->regExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="custom field 2" value="another value"  \/>/');

    expect($form)->stringContainsString('Need to change your email address? Unsubscribe using the form below, then simply sign up again.');
  }

  public function testItAppliesFieldsFilter() {
    $subscriber = $this->getSubscriber($this->getSegment());
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->addFilter('mailpoet_manage_subscription_page_form_fields', function($fields) {
        $fields[] = [
          'type' => 'text',
          'name' => 'Additional info',
          'id' => 'additional_info',
          'params' => [
            'label' => 'Additional info',
          ],
        ];
        return $fields;
    });
    $form = $this->formRenderer->renderForm($subscriber);
    expect($form)->regExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Additional info" value=""  \/>/');
  }

  private function getSegment(): SegmentEntity {
    $segment = new SegmentEntity('Test segment', SegmentEntity::TYPE_DEFAULT, 'Description');
    $segment->setDisplayInManageSubscriptionPage(1);
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function getSubscriber(SegmentEntity $segment = null): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setFirstName('Fname');
    $subscriber->setLastName('Lname');
    $subscriber->setEmail('subscriber@test.com');

    if ($segment) {
      $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
      $this->entityManager->persist($subscriberSegment);
      $subscriber->getSubscriberSegments()->add($subscriberSegment);
    }

    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    (new CustomFieldFactory())->withName('custom field 1')->withSubscriber($subscriber->getId(), 'some value')->create();
    (new CustomFieldFactory())->withName('custom field 2')->withSubscriber($subscriber->getId(), 'another value')->create();

    return $subscriber;
  }

  private function cleanup() {
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }

  public function _after() {
    $this->cleanup();
  }
}
