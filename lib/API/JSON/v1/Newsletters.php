<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\InvalidStateException;
use MailPoet\Listing;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\Security;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Newsletters extends APIEndpoint {

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterListingRepository */
  private $newsletterListingRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  /** @var Emoji */
  private $emoji;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var SendPreviewController */
  private $sendPreviewController;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  public function __construct(
    Listing\Handler $listingHandler,
    WPFunctions $wp,
    SettingsController $settings,
    CronHelper $cronHelper,
    NewslettersRepository $newslettersRepository,
    NewsletterListingRepository $newsletterListingRepository,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    PostNotificationScheduler $postNotificationScheduler,
    Emoji $emoji,
    SubscribersFeature $subscribersFeature,
    SendPreviewController $sendPreviewController,
    NewsletterSaveController $newsletterSaveController,
    NewsletterUrl $newsletterUrl
  ) {
    $this->listingHandler = $listingHandler;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->emoji = $emoji;
    $this->subscribersFeature = $subscribersFeature;
    $this->sendPreviewController = $sendPreviewController;
    $this->newsletterSaveController = $newsletterSaveController;
    $this->newsletterUrl = $newsletterUrl;
  }

  public function get($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
      NewslettersResponseBuilder::RELATION_OPTIONS,
      NewslettersResponseBuilder::RELATION_QUEUE,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function getWithStats($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
        NewslettersResponseBuilder::RELATION_SEGMENTS,
        NewslettersResponseBuilder::RELATION_OPTIONS,
        NewslettersResponseBuilder::RELATION_QUEUE,
        NewslettersResponseBuilder::RELATION_TOTAL_SENT,
        NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    $response['preview_url'] = $this->getViewInBrowserUrl($newsletter);
    return $this->successResponse($response);
  }

  public function save($data = []) {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);
    $newsletter = $this->newsletterSaveController->save($data);
    $response = $this->newslettersResponseBuilder->build($newsletter);
    $previewUrl = $this->getViewInBrowserUrl($newsletter);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_save_after', $response);
    return $this->successResponse($response, ['preview_url' => $previewUrl]);
  }

  public function setStatus($data = []) {
    $status = (isset($data['status']) ? $data['status'] : null);

    if (!$status) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('You need to specify a status.', 'mailpoet'),
      ]);
    }

    if ($status === NewsletterEntity::STATUS_ACTIVE && $this->subscribersFeature->check()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Subscribers limit reached.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    $newsletter = $this->getNewsletter($data);
    if ($newsletter === null) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $tracking = $this->settings->get('tracking');
    $tracking_enabled = !empty($tracking['enabled']) && $tracking['enabled'] === "1";
    if ( !$tracking_enabled && $newsletter->getType() === NewsletterEntity::TYPE_RE_ENGAGEMENT && $status === NewsletterEntity::STATUS_ACTIVE) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Re-engagement emails are disabled because open and click tracking is disabled.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    $this->newslettersRepository->prefetchOptions([$newsletter]);
    $newsletter->setStatus($status);

    // if there are past due notifications, reschedule them for the next send date
    if ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION && $status === NewsletterEntity::STATUS_ACTIVE) {
      $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
      if ($scheduleOption === null) {
        return $this->errorResponse([
          APIError::BAD_REQUEST => __('This email has incorrect state.', 'mailpoet'),
        ]);
      }
      $nextRunDate = Scheduler::getNextRunDate($scheduleOption->getValue());
      $queues = $newsletter->getQueues();
      foreach ($queues as $queue) {
        $task = $queue->getTask();
        if (
          $task &&
          $task->getScheduledAt() <= Carbon::createFromTimestamp($this->wp->currentTime('timestamp')) &&
          $task->getStatus() === SendingQueueEntity::STATUS_SCHEDULED
        ) {
          $nextRunDate = $nextRunDate ? Carbon::createFromFormat('Y-m-d H:i:s', $nextRunDate) : null;
          if ($nextRunDate === false) {
            throw InvalidStateException::create()->withMessage('Invalid next run date generated');
          }
          $task->setScheduledAt($nextRunDate);
        }
      }
      $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    }

    $this->newslettersRepository->flush();

    return $this->successResponse(
      $this->newslettersResponseBuilder->build($newsletter)
    );
  }

  public function restore($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkRestore([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkTrash([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->wp->doAction('mailpoet_api_newsletters_delete_before', [$newsletter->getId()]);
      $this->newslettersRepository->bulkDelete([$newsletter->getId()]);
      $this->wp->doAction('mailpoet_api_newsletters_delete_after', [$newsletter->getId()]);
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function duplicate($data = []) {
    $newsletter = $this->getNewsletter($data);

    if ($newsletter instanceof NewsletterEntity) {
      $duplicate = $this->newsletterSaveController->duplicate($newsletter);
      $this->wp->doAction('mailpoet_api_newsletters_duplicate_after', $newsletter, $duplicate);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($duplicate),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function showPreview($data = []) {
    if (empty($data['body'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Newsletter data is missing.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newsletter->setBody(
      json_decode($this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', $data['body']), true)
    );
    $this->newslettersRepository->flush();

    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function sendPreview($data = []) {
    if (empty($data['subscriber'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please specify receiver information.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    try {
      $this->sendPreviewController->sendPreview($newsletter, $data['subscriber']);
    } catch (SendPreviewException $e) {
      return $this->errorResponse([APIError::BAD_REQUEST => $e->getMessage()]);
    } catch (\Throwable $e) {
      return $this->errorResponse([$e->getCode() => $e->getMessage()]);
    }
    return $this->successResponse($this->newslettersResponseBuilder->build($newsletter));
  }

  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->newsletterListingRepository->getData($definition);
    $count = $this->newsletterListingRepository->getCount($definition);
    $filters = $this->newsletterListingRepository->getFilters($definition);
    $groups = $this->newsletterListingRepository->getGroups($definition);

    $this->fixMissingHash($items); // Fix for MAILPOET-3275. Remove after May 2021
    $data = [];
    foreach ($this->newslettersResponseBuilder->buildForListing($items) as $newsletterData) {
      $data[] = $this->wp->applyFilters('mailpoet_api_newsletters_listing_item', $newsletterData);
    }

    return $this->successResponse($data, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => $this->cronHelper->isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql'),
    ]);
  }

  public function bulkAction($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data['listing']);
    $ids = $this->newsletterListingRepository->getActionableIds($definition);
    if ($data['action'] === 'trash') {
      $this->newslettersRepository->bulkTrash($ids);
    } elseif ($data['action'] === 'restore') {
      $this->newslettersRepository->bulkRestore($ids);
    } elseif ($data['action'] === 'delete') {
      $this->wp->doAction('mailpoet_api_newsletters_delete_before', $ids);
      $this->newslettersRepository->bulkDelete($ids);
      $this->wp->doAction('mailpoet_api_newsletters_delete_after', $ids);
    } else {
      throw UnexpectedValueException::create()
        ->withErrors([APIError::BAD_REQUEST => "Invalid bulk action '{$data['action']}' provided."]);
    }
    return $this->successResponse(null, ['count' => count($ids)]);
  }

  public function create($data = []) {
    try {
      $newsletter = $this->newsletterSaveController->save($data);
    } catch (ValidationException $exception) {
      return $this->badRequest(['Please specify a type.']);
    }
    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response);
  }

  /** @return NewsletterEntity|null */
  private function getNewsletter(array $data) {
    return isset($data['id'])
      ? $this->newslettersRepository->findOneById((int)$data['id'])
      : null;
  }

  private function getViewInBrowserUrl(NewsletterEntity $newsletter): string {
    $this->fixMissingHash([$newsletter]); // Fix for MAILPOET-3275. Remove after May 2021
    $url = $this->newsletterUrl->getViewInBrowserUrl(
      (object)[
        'id' => $newsletter->getId(),
        'hash' => $newsletter->getHash(),
      ]
    );

    // strip protocol to avoid mix content error
    return preg_replace('/^https?:/i', '', $url);
  }

  /**
   * Some Newsletters were created without a hash due to a bug MAILPOET-3275
   * We can remove this fix after May 2021 since by then most users should have their data fixed
   * @param NewsletterEntity[] $newsletters
   */
  private function fixMissingHash(array $newsletters) {
    foreach ($newsletters as $newsletter) {
      if (!$newsletter instanceof NewsletterEntity || $newsletter->getHash() !== null) {
        continue;
      }
      $newsletter->setHash(Security::generateHash());
      $this->newslettersRepository->flush();
    }
  }
}
