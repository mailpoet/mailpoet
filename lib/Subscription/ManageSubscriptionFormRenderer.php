<?php

namespace MailPoet\Subscription;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Form\Block\Date as FormBlockDate;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Util\Helpers;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WP\Functions as WPFunctions;

class ManageSubscriptionFormRenderer {
  /** @var SettingsController */
  private $settings;

  /** @var UrlHelper */
  private $urlHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var FormRenderer */
  private $formRenderer;

  /** @var FormBlockDate */
  private $dateBlock;

  /** @var TemplateRenderer */
  private $templateRenderer;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    UrlHelper $urlHelper,
    LinkTokens $linkTokens,
    FormRenderer $formRenderer,
    FormBlockDate $dateBlock,
    TemplateRenderer $templateRenderer
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->urlHelper = $urlHelper;
    $this->linkTokens = $linkTokens;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
    $this->templateRenderer = $templateRenderer;
  }

  public function renderForm(Subscriber $subscriber): string {
    $basicFields = $this->getBasicFields($subscriber);
    $customFields = $this->getCustomFields($subscriber);
    $segmentField = $this->getSegmentField($subscriber);

    $form = array_merge(
      $basicFields,
      $customFields,
      [
        $segmentField,
        [
          'id' => 'submit',
          'type' => 'submit',
          'params' => [
            'label' => __('Save', 'mailpoet'),
          ],
        ],
      ]
    );

    $form = $this->wp->applyFilters('mailpoet_manage_subscription_page_form_fields', $form);

    $templateData = [
      'actionUrl' => admin_url('admin-post.php'),
      'redirectUrl' => $this->urlHelper->getCurrentUrl(),
      'email' => $subscriber->email,
      'token' => $this->linkTokens->getToken($subscriber),
      'editEmailInfo' => __('Need to change your email address? Unsubscribe here, then simply sign up again.', 'mailpoet'),
      'formHtml' => $this->formRenderer->renderBlocks($form, [], $honeypot = false),
    ];

    if ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()) {
      $wpCurrentUser = $this->wp->wpGetCurrentUser();
      if ($wpCurrentUser->user_email === $subscriber->email) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $templateData['editEmailInfo'] = Helpers::replaceLinkTags(
          __('[link]Edit your profile[/link] to update your email.', 'mailpoet'),
          $this->wp->getEditProfileUrl(),
          ['target' => '_blank']
        );
      } else {
        $templateData['editEmailInfo'] = Helpers::replaceLinkTags(
          __('[link]Log in to your account[/link] to update your email.', 'mailpoet'),
          $this->wp->wpLoginUrl(),
          ['target' => '_blank']
        );
      }
    }

    return $this->templateRenderer->render('subscription/manage_subscription.html', $templateData);
  }

  private function getCustomFields(Subscriber $subscriber): array {
    return array_map(function($customField) use($subscriber) {
      $customField->id = 'cf_' . $customField->id;
      $customField = $customField->asArray();
      $customField['params']['value'] = $subscriber->{$customField['id']};

      if ($customField['type'] === 'date') {
        $dateFormats = $this->dateBlock->getDateFormats();
        $customField['params']['date_format'] = array_shift(
          $dateFormats[$customField['params']['date_type']]
        );
      }
      if (!isset($customField['params']['label'])) {
        $customField['params']['label'] = $customField['name'];
      }

      return $customField;
    }, CustomField::findMany());
  }

  private function getBasicFields(Subscriber $subscriber): array {
    return [
      [
        'id' => 'first_name',
        'type' => 'text',
        'params' => [
          'label' => __('First name', 'mailpoet'),
          'value' => $subscriber->firstName,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()),
        ],
      ],
      [
        'id' => 'last_name',
        'type' => 'text',
        'params' => [
          'label' => __('Last name', 'mailpoet'),
          'value' => $subscriber->lastName,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()),
        ],
      ],
      [
        'id' => 'status',
        'type' => 'select',
        'params' => [
          'required' => true,
          'label' => __('Status', 'mailpoet'),
          'values' => [
            [
              'value' => [
                Subscriber::STATUS_SUBSCRIBED => __('Subscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_SUBSCRIBED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_UNSUBSCRIBED => __('Unsubscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_BOUNCED => __('Bounced', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_BOUNCED
              ),
              'is_disabled' => true,
              'is_hidden' => (
                $subscriber->status !== Subscriber::STATUS_BOUNCED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_INACTIVE => __('Inactive', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_INACTIVE
              ),
              'is_hidden' => (
                $subscriber->status !== Subscriber::STATUS_INACTIVE
              ),
            ],
          ],
        ],
      ],
    ];
  }

  private function getSegmentField(Subscriber $subscriber): array {
    $segmentIds = $this->settings->get('subscription.segments', []);
    if (!empty($segmentIds)) {
      $segments = Segment::getPublic()
        ->whereIn('id', $segmentIds)
        ->findMany();
    } else {
      $segments = Segment::getPublic()
        ->findMany();
    }
    $subscribedSegmentIds = [];
    if (!empty($subscriber->subscriptions)) {
      foreach ($subscriber->subscriptions as $subscription) {
        if ($subscription['status'] === Subscriber::STATUS_SUBSCRIBED) {
          $subscribedSegmentIds[] = $subscription['segment_id'];
        }
      }
    }

    $segments = array_map(function($segment) use($subscribedSegmentIds) {
      return [
        'id' => $segment->id,
        'name' => $segment->name,
        'is_checked' => in_array($segment->id, $subscribedSegmentIds),
      ];
    }, $segments);

    return [
      'id' => 'segments',
      'type' => 'segment',
      'params' => [
        'label' => __('Your lists', 'mailpoet'),
        'values' => $segments,
      ],
    ];
  }
}
