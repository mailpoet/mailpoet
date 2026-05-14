<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Block\Date as FormBlockDate;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Util\Helpers;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;

class ManageSubscriptionFormRenderer {
  const FORM_STATE_SUCCESS = 'success';
  const FORM_STATE_ERROR = 'error';
  const FORM_STATE_NOT_SUBMITTED = 'not_submitted';

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

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    WPFunctions $wp,
    UrlHelper $urlHelper,
    LinkTokens $linkTokens,
    FormRenderer $formRenderer,
    FormBlockDate $dateBlock,
    TemplateRenderer $templateRenderer,
    CustomFieldsRepository $customFieldsRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->wp = $wp;
    $this->urlHelper = $urlHelper;
    $this->linkTokens = $linkTokens;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
    $this->templateRenderer = $templateRenderer;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function renderForm(SubscriberEntity $subscriber, string $formState = self::FORM_STATE_NOT_SUBMITTED): string {
    $basicFields = $this->getBasicFields($subscriber);
    $customFields = $this->getCustomFields($subscriber);
    $segmentField = $this->getSegmentField($subscriber);
    $submitField = [
      'id' => 'submit',
      'type' => 'submit',
      'params' => [
        'label' => __('Save changes', 'mailpoet'),
      ],
    ];

    $form = array_merge(
      $basicFields,
      $customFields,
      [
        $segmentField,
        $submitField,
      ]
    );

    $filteredForm = $this->wp->applyFilters('mailpoet_manage_subscription_page_form_fields', $form);
    if (is_array($filteredForm)) {
      $form = $filteredForm;
    }

    $formSections = $this->splitFormSections($form);

    $templateData = [
      'actionUrl' => admin_url('admin-post.php'),
      'redirectUrl' => $this->urlHelper->getCurrentUrl(),
      'email' => $subscriber->getEmail(),
      'token' => $this->linkTokens->getToken($subscriber),
      'editEmailInfo' => __('Need to change your email address? Unsubscribe using the form below, then simply sign up again.', 'mailpoet'),
      'identityFieldsHtml' => $this->formRenderer->renderBlocks($formSections['identityFields'], [], null, $honeypot = false, $captcha = false),
      'additionalIdentityFieldsHtml' => $this->formRenderer->renderBlocks($formSections['additionalIdentityFields'], [], null, $honeypot = false, $captcha = false),
      'listFieldsHtml' => $this->formRenderer->renderBlocks($formSections['listFields'], [], null, $honeypot = false, $captcha = false),
      'submitHtml' => $this->formRenderer->renderBlocks($formSections['submitFields'], [], null, $honeypot = false, $captcha = false),
      'hasVisibleLists' => $formSections['hasVisibleLists'],
      'formState' => $formState,
    ];

    if ($subscriber->isWPUser() || $subscriber->getIsWoocommerceUser()) {
      $wpCurrentUser = $this->wp->wpGetCurrentUser();
      if ($wpCurrentUser->user_email === $subscriber->getEmail()) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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

  private function splitFormSections(array $form): array {
    $identityFields = [];
    $additionalIdentityFields = [];
    $listFields = [];
    $submitFields = [];
    $hasVisibleLists = false;
    $placeAdditionalIdentityFieldsAfterNoListNotice = false;

    foreach ($form as $field) {
      if (!is_array($field)) {
        continue;
      }
      $params = isset($field['params']) && is_array($field['params']) ? $field['params'] : [];

      if (($field['type'] ?? null) === 'submit') {
        $submitFields[] = $field;
        continue;
      }

      if (($field['type'] ?? null) === 'segment' && (($params['display_mode'] ?? null) === 'manage_subscription_choices')) {
        if (!empty($params['values']) && is_array($params['values'])) {
          $hasVisibleLists = true;
          $listFields[] = $field;
        }
        continue;
      }

      if ($placeAdditionalIdentityFieldsAfterNoListNotice) {
        $additionalIdentityFields[] = $field;
      } else {
        $identityFields[] = $field;
      }
      if (($field['id'] ?? null) === 'status') {
        $placeAdditionalIdentityFieldsAfterNoListNotice = true;
      }
    }

    return [
      'identityFields' => $identityFields,
      'additionalIdentityFields' => $additionalIdentityFields,
      'listFields' => $listFields,
      'submitFields' => $submitFields,
      'hasVisibleLists' => $hasVisibleLists,
    ];
  }

  private function getCustomFields(SubscriberEntity $subscriber): array {
    $customFieldValues = [];
    foreach ($subscriber->getSubscriberCustomFields() as $subscriberCustomField) {
      $customField = $subscriberCustomField->getCustomField();
      if (!$customField) continue;

      $customFieldValues[$customField->getId()] = $subscriberCustomField->getValue();
    }
    return array_map(function (CustomFieldEntity $customFieldEntity) use ($customFieldValues) {
      $customField = [
        'id' => 'cf_' . $customFieldEntity->getId(),
        'name' => $customFieldEntity->getName(),
        'type' => $customFieldEntity->getType(),
        'params' => $customFieldEntity->getParams(),
      ];

      $customField['params']['value'] = $customFieldValues[$customFieldEntity->getId()] ?? null;

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
    }, $this->customFieldsRepository->findAllActive());
  }

  private function getBasicFields(SubscriberEntity $subscriber): array {
    return [
      [
        'id' => 'first_name',
        'type' => 'text',
        'params' => [
          'label' => __('First name', 'mailpoet'),
          'value' => $subscriber->getFirstName(),
          'disabled' => ($subscriber->isWPUser() || $subscriber->getIsWoocommerceUser()),
        ],
      ],
      [
        'id' => 'last_name',
        'type' => 'text',
        'params' => [
          'label' => __('Last name', 'mailpoet'),
          'value' => $subscriber->getLastName(),
          'disabled' => ($subscriber->isWPUser() || $subscriber->getIsWoocommerceUser()),
        ],
      ],
      [
        'id' => 'status',
        'type' => 'select',
        'params' => [
          'required' => true,
          'label' => __('Email subscription status', 'mailpoet'),
          'description' => __('This controls whether you receive emails overall. Your list choices below control which public lists you receive.', 'mailpoet'),
          'class_name' => 'mailpoet-manage-subscription-status',
          'input_id' => 'mailpoet_manage_subscription_status',
          'values' => [
            [
              'value' => [
                SubscriberEntity::STATUS_SUBSCRIBED => __('Subscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED
              ),
            ],
            [
              'value' => [
                SubscriberEntity::STATUS_UNSUBSCRIBED => __('Unsubscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->getStatus() === SubscriberEntity::STATUS_UNSUBSCRIBED
              ),
            ],
            [
              'value' => [
                SubscriberEntity::STATUS_BOUNCED => __('Bounced', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->getStatus() === SubscriberEntity::STATUS_BOUNCED
              ),
              'is_disabled' => true,
              'is_hidden' => (
                $subscriber->getStatus() !== SubscriberEntity::STATUS_BOUNCED
              ),
            ],
            [
              'value' => [
                SubscriberEntity::STATUS_INACTIVE => __('Inactive', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->getStatus() === SubscriberEntity::STATUS_INACTIVE
              ),
              'is_hidden' => (
                $subscriber->getStatus() !== SubscriberEntity::STATUS_INACTIVE
              ),
            ],
          ],
        ],
      ],
    ];
  }

  private function getSegmentField(SubscriberEntity $subscriber): array {
    // Get default segments
    $criteria = [
      'type' => SegmentEntity::TYPE_DEFAULT,
      'deletedAt' => null,
      'displayInManageSubscriptionPage' => true,
    ];
    $segments = $this->segmentsRepository->findBy($criteria, ['name' => Criteria::ASC]);

    $subscribedSegmentIds = [];
    foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
      $segment = $subscriberSegment->getSegment();
      if (!$segment) continue;

      if ($subscriberSegment->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
        $subscribedSegmentIds[] = $segment->getId();
      }
    }

    $segments = array_map(function (SegmentEntity $segment) use ($subscribedSegmentIds) {
      return [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'public_description' => $segment->getPublicDescription(),
        'is_checked' => in_array($segment->getId(), $subscribedSegmentIds),
      ];
    }, $segments);

    return [
      'id' => 'segments',
      'type' => 'segment',
      'params' => [
        'label' => __('Your lists', 'mailpoet'),
        'description' => __('Choose Yes for lists you want to receive and No for lists you do not want to receive.', 'mailpoet'),
        'display_mode' => 'manage_subscription_choices',
        'values' => $segments,
      ],
    ];
  }
}
