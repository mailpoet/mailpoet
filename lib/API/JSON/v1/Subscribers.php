<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\BulkAction;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\WP\Functions as WPFunctions;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => ['subscribe' => AccessControl::NO_ACCESS_RESTRICTION],
  ];

  /** @var Listing\BulkActionController */
  private $bulkActionController;


  /** @var SubscriberActions */
  private $subscriberActions;

  /** @var RequiredCustomFieldValidator */
  private $requiredCustomFieldValidator;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var Captcha */
  private $subscriptionCaptcha;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var CaptchaSession */
  private $captchaSession;

  /** @var ConfirmationEmailMailer; */
  private $confirmationEmailMailer;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var FieldNameObfuscator */
  private $fieldNameObfuscator;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  public function __construct(
    Listing\BulkActionController $bulkActionController,
    SubscriberActions $subscriberActions,
    RequiredCustomFieldValidator $requiredCustomFieldValidator,
    Listing\Handler $listingHandler,
    Captcha $subscriptionCaptcha,
    WPFunctions $wp,
    SettingsController $settings,
    CaptchaSession $captchaSession,
    ConfirmationEmailMailer $confirmationEmailMailer,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    Unsubscribes $unsubscribesTracker,
    SubscribersRepository $subscribersRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder,
    SubscriberListingRepository $subscriberListingRepository,
    FieldNameObfuscator $fieldNameObfuscator
  ) {
    $this->bulkActionController = $bulkActionController;
    $this->subscriberActions = $subscriberActions;
    $this->requiredCustomFieldValidator = $requiredCustomFieldValidator;
    $this->listingHandler = $listingHandler;
    $this->subscriptionCaptcha = $subscriptionCaptcha;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->captchaSession = $captchaSession;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->unsubscribesTracker = $unsubscribesTracker;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
    $this->subscriberListingRepository = $subscriberListingRepository;
  }

  public function get($data = []) {
    $subscriber = $this->getSubscriber($data);
    if (!$subscriber instanceof SubscriberEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
    $result = $this->subscribersResponseBuilder->build($subscriber);
    return $this->successResponse($result);
  }

  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->subscriberListingRepository->getData($definition);
    $count = $this->subscriberListingRepository->getCount($definition);
    $filters = $this->subscriberListingRepository->getFilters($definition);
    $groups = $this->subscriberListingRepository->getGroups($definition);
    $subscribers = $this->subscribersResponseBuilder->buildForListing($items);
    if ($data['filter']['segment'] ?? false) {
      foreach ($subscribers as $key => $subscriber) {
        $subscribers[$key] = $this->preferUnsubscribedStatusFromSegment($subscriber, $data['filter']['segment']);
      }
    }
    return $this->successResponse($subscribers, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
    ]);
  }

  private function preferUnsubscribedStatusFromSegment(array $subscriber, $segmentId) {
    $segmentStatus = $this->findSegmentStatus($subscriber, $segmentId);

    if ($segmentStatus === Subscriber::STATUS_UNSUBSCRIBED) {
      $subscriber['status'] = $segmentStatus;
    }
    return $subscriber;
  }

  private function findSegmentStatus(array $subscriber, $segmentId) {
    foreach ($subscriber['subscriptions'] as $segment) {
      if ($segment['segment_id'] === $segmentId) {
        return $segment['status'];
      }
    }
  }

  public function subscribe($data = []) {
    $formId = (isset($data['form_id']) ? (int)$data['form_id'] : false);
    $form = Form::findOne($formId);
    unset($data['form_id']);

    if (!$form instanceof Form) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please specify a valid form ID.', 'mailpoet'),
      ]);
    }
    if (!empty($data['email'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please leave the first field empty.', 'mailpoet'),
      ]);
    }

    $captchaSettings = $this->settings->get('captcha');

    if (!empty($captchaSettings['type'])
      && $captchaSettings['type'] === Captcha::TYPE_BUILTIN
    ) {
      $captchaSessionId = isset($data['captcha_session_id']) ? $data['captcha_session_id'] : null;
      $this->captchaSession->init($captchaSessionId);
      if (!isset($data['captcha'])) {
        // Save form data to session
        $this->captchaSession->setFormData(array_merge($data, ['form_id' => $formId]));
      } elseif ($this->captchaSession->getFormData()) {
        // Restore form data from session
        $data = array_merge($this->captchaSession->getFormData(), ['captcha' => $data['captcha']]);
      }
      // Otherwise use the post data
    }

    $data = $this->deobfuscateFormPayload($data);

    try {
      $this->requiredCustomFieldValidator->validate($data, $form);
    } catch (\Exception $e) {
      return $this->badRequest([APIError::BAD_REQUEST => $e->getMessage()]);
    }

    $segmentIds = (!empty($data['segments'])
      ? (array)$data['segments']
      : []
    );
    $segmentIds = $form->filterSegments($segmentIds);
    unset($data['segments']);

    if (empty($segmentIds)) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please select a list.', 'mailpoet'),
      ]);
    }

    $captchaValidationResult = $this->validateCaptcha($captchaSettings, $data);
    if ($captchaValidationResult instanceof APIResponse) {
      return $captchaValidationResult;
    }

    // only accept fields defined in the form
    $formFields = $form->getFieldList();
    $data = array_intersect_key($data, array_flip($formFields));

    // make sure we don't allow too many subscriptions with the same ip address
    $timeout = SubscriptionThrottling::throttle();

    if ($timeout > 0) {
      $timeToWait = SubscriptionThrottling::secondsToTimeString($timeout);
      $meta = [];
      $meta['refresh_captcha'] = true;
      return $this->badRequest([
        APIError::BAD_REQUEST => sprintf(WPFunctions::get()->__('You need to wait %s before subscribing again.', 'mailpoet'), $timeToWait),
      ], $meta);
    }

    $subscriber = $this->subscriberActions->subscribe($data, $segmentIds);
    $errors = $subscriber->getErrors();

    if ($errors !== false) {
      return $this->badRequest($errors);
    } else {
      if (!empty($captchaSettings['type']) && $captchaSettings['type'] === Captcha::TYPE_BUILTIN) {
        // Captcha has been verified, invalidate the session vars
        $this->captchaSession->reset();
      }

      $meta = [];

      if ($form !== false) {
        // record form statistics
        StatisticsForms::record($form->id, $subscriber->id);

        $form = $form->asArray();

        if (!empty($form['settings']['on_success'])) {
          if ($form['settings']['on_success'] === 'page') {
            // redirect to a page on a success, pass the page url in the meta
            $meta['redirect_url'] = WPFunctions::get()->getPermalink($form['settings']['success_page']);
          } else if ($form['settings']['on_success'] === 'url') {
            $meta['redirect_url'] = $form['settings']['success_url'];
          }
        }
      }

      return $this->successResponse(
        [],
        $meta
      );
    }
  }

  private function deobfuscateFormPayload($data) {
    return $this->fieldNameObfuscator->deobfuscateFormPayload($data);
  }

  private function validateCaptcha($captchaSettings, $data) {
    if (empty($captchaSettings['type'])) {
      return true;
    }

    $isBuiltinCaptchaRequired = false;
    if ($captchaSettings['type'] === Captcha::TYPE_BUILTIN) {
      $isBuiltinCaptchaRequired = $this->subscriptionCaptcha->isRequired(isset($data['email']) ? $data['email'] : '');
      if ($isBuiltinCaptchaRequired && empty($data['captcha'])) {
        $meta = [];
        $meta['redirect_url'] = $this->subscriptionUrlFactory->getCaptchaUrl($this->captchaSession->getId());
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Please fill in the CAPTCHA.', 'mailpoet'),
        ], $meta);
      }
    }

    if ($captchaSettings['type'] === Captcha::TYPE_RECAPTCHA && empty($data['recaptcha'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please check the CAPTCHA.', 'mailpoet'),
      ]);
    }

    if ($captchaSettings['type'] === Captcha::TYPE_RECAPTCHA) {
      $res = empty($data['recaptcha']) ? $data['recaptcha-no-js'] : $data['recaptcha'];
      $res = WPFunctions::get()->wpRemotePost('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
          'secret' => $captchaSettings['recaptcha_secret_token'],
          'response' => $res,
        ],
      ]);
      if (is_wp_error($res)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet'),
        ]);
      }
      $res = json_decode(wp_remote_retrieve_body($res));
      if (empty($res->success)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet'),
        ]);
      }
    } elseif ($captchaSettings['type'] === Captcha::TYPE_BUILTIN && $isBuiltinCaptchaRequired) {
      $captchaHash = $this->captchaSession->getCaptchaHash();
      if (empty($captchaHash)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Please regenerate the CAPTCHA.', 'mailpoet'),
        ]);
      } elseif (!hash_equals(strtolower($data['captcha']), strtolower($captchaHash))) {
        $this->captchaSession->setCaptchaHash(null);
        $meta = [];
        $meta['refresh_captcha'] = true;
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('The characters entered do not match with the previous CAPTCHA.', 'mailpoet'),
        ], $meta);
      }
    }

    return true;
  }

  public function save($data = []) {
    if (empty($data['segments'])) {
      $data['segments'] = [];
    }
    $data['segments'] = array_merge($data['segments'], $this->getNonDefaultSubscribedSegments($data));
    $newSegments = $this->findNewSegments($data);
    if (isset($data['id']) && (int)$data['id'] > 0) {
      $oldSubscriber = Subscriber::findOne((int)$data['id']);
      if (
        isset($data['status'])
        && ($data['status'] === SubscriberEntity::STATUS_UNSUBSCRIBED)
        && ($oldSubscriber instanceof Subscriber)
        && ($oldSubscriber->status !== SubscriberEntity::STATUS_UNSUBSCRIBED)
      ) {
        $currentUser = $this->wp->wpGetCurrentUser();
        $this->unsubscribesTracker->track(
          (int)$oldSubscriber->id,
          StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR,
          null,
          $currentUser->display_name // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        );
      }
    }
    $subscriber = Subscriber::createOrUpdate($data);
    $errors = $subscriber->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    }

    if ($subscriber->isNew()) {
      $subscriber = Source::setSource($subscriber, Source::ADMINISTRATOR);
      $subscriber->save();
    }

    if (!empty($newSegments)) {
      $scheduler = new WelcomeScheduler();
      $scheduler->scheduleSubscriberWelcomeNotification($subscriber->id, $newSegments);
    }

    return $this->successResponse(
      Subscriber::findOne($subscriber->id)->asArray()
    );
  }

  private function getNonDefaultSubscribedSegments(array $data) {
    if (!isset($data['id']) || (int)$data['id'] <= 0) {
      return [];
    }

    $subscribedSegmentIds = [];
    $nonDefaultSegment = Segment::select('id')
      ->whereNotEqual('type', Segment::TYPE_DEFAULT)
      ->findArray();
    $nonDefaultSegmentIds = array_map(function($segment) {
      return $segment['id'];
    }, $nonDefaultSegment);

    $subscribedSegments = SubscriberSegment::select('segment_id')
      ->where('subscriber_id', $data['id'])
      ->where('status', Subscriber::STATUS_SUBSCRIBED)
      ->whereIn('segment_id', $nonDefaultSegmentIds)
      ->findArray();
    $subscribedSegmentIds = array_map(function($segment) {
      return $segment['segment_id'];
    }, $subscribedSegments);

    return $subscribedSegmentIds;
  }

  private function findNewSegments(array $data) {
    $oldSegmentIds = [];
    if (isset($data['id']) && (int)$data['id'] > 0) {
      $oldSegments = SubscriberSegment::where('subscriber_id', $data['id'])->findMany();
      foreach ($oldSegments as $oldSegment) {
        $oldSegmentIds[] = $oldSegment->segmentId;
      }
    }
    return array_diff($data['segments'], $oldSegmentIds);
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->restore();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->trash();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function sendConfirmationEmail($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      if ($this->confirmationEmailMailer->sendConfirmationEmail($subscriber)) {
        return $this->successResponse();
      }
      return $this->errorResponse();
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function bulkAction($data = []) {
    try {
      if (!isset($data['listing']['filter']['segment'])) {
        return $this->successResponse(
          null,
          $this->bulkActionController->apply('\MailPoet\Models\Subscriber', $data)
        );
      } else {
        $bulkAction = new BulkAction($data);
        return $this->successResponse(null, $bulkAction->apply());
      }
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  /**
   * @param array $data
   * @return SubscriberEntity|null
   */
  private function getSubscriber($data) {
    return isset($data['id'])
      ? $this->subscribersRepository->findOneById((int)$data['id'])
      : null;
  }
}
