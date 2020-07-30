<?php

namespace MailPoet\Subscription;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Models\Subscriber;
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
    $form = $this->formRenderer->renderForm(Subscriber::findOne($subscriber->getId()));
    expect($form)->regExp('/<form class="mailpoet-manage-subscription" method="post" action="[a-z0-9:\/\.]+wp-admin\/admin-post.php" novalidate>/');
    expect($form)->contains('<input type="hidden" name="data[email]" value="subscriber@test.com" />');
    expect($form)->regExp('/<input type="text" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="First name" value="Fname" data-automation-id="form_first_name" \/>/');
    expect($form)->regExp('/<input type="text" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Last name" value="Lname" data-automation-id="form_last_name" \/>/');
    expect($form)->regExp('/<input type="checkbox" class="mailpoet_checkbox" name="data\[[a-zA-Z0-9=_]+\]\[\]" value="1"  data-parsley-required="true" data-parsley-group="segments" data-parsley-errors-container="\.mailpoet_error_segments" data-parsley-required-message="Please select a list" \/> Test segment/');
    expect($form)->contains('Need to change your email address? Unsubscribe here, then simply sign up again.');
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
    $form = $this->formRenderer->renderForm(Subscriber::findOne($subscriber->getId()));
    expect($form)->regExp('/<input type="text" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Additional info" value=""  \/>/');
  }

  private function getSegment(): SegmentEntity {
    $segment = new SegmentEntity();
    $segment->setName('Test segment');
    $segment->setDescription('Description');
    $segment->setType(SegmentEntity::TYPE_DEFAULT);
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
      $subscriber->getSegments()->add($subscriberSegment);
    }

    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function cleanup() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }

  public function _after() {
    $this->cleanup();
  }
}
