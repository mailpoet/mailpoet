<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\WP\Functions as WPFunctions;

class ManageSubscriptionFormRendererTest extends \MailPoetTest {
  /** @var ManageSubscriptionFormRenderer */
  private $formRenderer;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SegmentEntity */
  private $segment;

  public function _before() {
    $this->segment = $this->getSegment();
    $this->subscriber = $this->getSubscriber($this->segment);
    $this->formRenderer = $this->diContainer->get(ManageSubscriptionFormRenderer::class);
    parent::_before();
  }

  public function testItGeneratesForm() {
    $form = $this->formRenderer->renderForm($this->subscriber);
    verify($form)->stringMatchesRegExp('/<form class="mailpoet-manage-subscription" method="post" action="[a-z0-9:\/\._]+wp-admin\/admin-post.php" novalidate>/');
    verify($form)->stringContainsString('<input type="hidden" name="data[email]" value="subscriber@test.com" />');
    verify($form)->stringMatchesRegExp('/<input type="text" autocomplete="given-name" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="First name" value="Fname" data-automation-id="form_first_name" data-parsley-errors-container=".mailpoet_error_[a-zA-Z0-9]{5}" data-parsley-names=\'\[&quot;Please specify a valid name.&quot;,&quot;Addresses in names are not permitted, please add your name instead\.&quot;\]\'\/>/');
    verify($form)->stringMatchesRegExp('/<input type="text" autocomplete="family-name" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Last name" value="Lname" data-automation-id="form_last_name" data-parsley-errors-container=".mailpoet_error_[a-zA-Z0-9]{5}" data-parsley-names=\'\[&quot;Please specify a valid name.&quot;,&quot;Addresses in names are not permitted, please add your name instead\.&quot;\]\'\/>/');
    verify($form)->stringContainsString('data-automation-id="manage_subscription_lists"');
    verify($form)->stringContainsString('data-automation-id="manage_subscription_list_' . $this->segment->getId() . '"');
    verify($form)->stringContainsString('name="data[segment_choices][' . $this->segment->getId() . ']" value="subscribed" checked="checked" data-automation-id="manage_subscription_list_' . $this->segment->getId() . '_yes"');
    verify($form)->stringContainsString('name="data[segment_choices][' . $this->segment->getId() . ']" value="unsubscribed" data-automation-id="manage_subscription_list_' . $this->segment->getId() . '_no"');
    verify($form)->stringContainsString('Public description for subscribers');
    verify($form)->stringContainsString('Email subscription status');
    verify($form)->stringMatchesRegExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="custom field 1" value="some value"  data-parsley-errors-container=".mailpoet_error_[a-zA-Z0-9]{5}"\/>/');
    verify($form)->stringMatchesRegExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="custom field 2" value="another value"  data-parsley-errors-container=".mailpoet_error_[a-zA-Z0-9]{5}"\/>/');

    verify($form)->stringContainsString('Need to change your email address? Unsubscribe using the form below, then simply sign up again.');
  }

  public function testItAppliesFieldsFilter() {
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
    $form = $this->formRenderer->renderForm($this->subscriber);
    verify($form)->stringMatchesRegExp('/<input type="text" autocomplete="on" class="mailpoet_text" name="data\[[a-zA-Z0-9=_]+\]" title="Additional info" value=""  data-parsley-errors-container=".mailpoet_error_[a-zA-Z0-9]{5}"\/>/');
  }

  public function testItEscapesManageSubscriptionListNamesAndPublicDescriptions(): void {
    $this->segment->setName('List <strong>name</strong>');
    $this->segment->setPublicDescription('Public <script>alert(1)</script> description');
    $this->entityManager->flush();

    $form = $this->formRenderer->renderForm($this->subscriber);

    verify($form)->stringContainsString('List &lt;strong&gt;name&lt;/strong&gt;');
    verify($form)->stringContainsString('Public &lt;script&gt;alert(1)&lt;/script&gt; description');
    verify($form)->stringNotContainsString('List <strong>name</strong>');
    verify($form)->stringNotContainsString('Public <script>alert(1)</script> description');
  }

  public function testItOmitsListsSectionWhenThereAreNoVisibleLists(): void {
    $this->segment->setDisplayInManageSubscriptionPage(false);
    $this->entityManager->flush();

    $form = $this->formRenderer->renderForm($this->subscriber);

    verify($form)->stringNotContainsString('data-automation-id="manage_subscription_lists"');
    verify($form)->stringContainsString('There are no individual list preferences to manage for this email address.');
    verify($form)->stringContainsString('data-automation-id="subscribe-submit-button"');
  }

  private function getSegment(): SegmentEntity {
    $segment = new SegmentEntity('Test segment', SegmentEntity::TYPE_DEFAULT, 'Description');
    $segment->setPublicDescription('Public description for subscribers');
    $segment->setDisplayInManageSubscriptionPage(true);
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function getSubscriber(?SegmentEntity $segment = null): SubscriberEntity {
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
}
