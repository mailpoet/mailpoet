<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterQueueTask;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Listing;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Newsletters extends APIEndpoint {

  /** @var Listing\BulkActionController */
  private $bulkAction;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

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

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    WPFunctions $wp,
    SettingsController $settings,
    CronHelper $cronHelper,
    AuthorizedEmailsController $authorizedEmailsController,
    NewslettersRepository $newslettersRepository,
    NewsletterListingRepository $newsletterListingRepository,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    PostNotificationScheduler $postNotificationScheduler,
    Emoji $emoji,
    SubscribersFeature $subscribersFeature,
    SendPreviewController $sendPreviewController
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->emoji = $emoji;
    $this->subscribersFeature = $subscribersFeature;
    $this->sendPreviewController = $sendPreviewController;
  }

  public function get($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter) {
      $response = $this->newslettersResponseBuilder->build($newsletter, [
        NewslettersResponseBuilder::RELATION_SEGMENTS,
        NewslettersResponseBuilder::RELATION_OPTIONS,
        NewslettersResponseBuilder::RELATION_QUEUE,
      ]);

      $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
      return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function getWithStats($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter) {
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
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function save($data = []) {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);

    $segments = [];
    if (isset($data['segments'])) {
      $segments = $data['segments'];
      unset($data['segments']);
    }

    $options = [];
    if (isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    if (!empty($data['template_id'])) {
      $template = ContainerWrapper::getInstance()->get(NewsletterTemplatesRepository::class)->findOneById($data['template_id']);
      if ($template) {
        $data['body'] = json_encode($template->getBody());
      }
      unset($data['template_id']);
    }

    $oldNewsletter = null;
    if (isset($data['id'])) {
      $fetched = Newsletter::findOne(intval($data['id']));
      $oldNewsletter = $fetched instanceof Newsletter ? $fetched : null;
    }

    if (!empty($data['body'])) {
      $data['body'] = $this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', $data['body']);
    }
    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) return $this->badRequest($errors);
    // Re-fetch newsletter to sync changes made by DB
    // updated_at column use CURRENT_TIMESTAMP for update and this change is not updated automatically by ORM
    $newsletter = Newsletter::findOne($newsletter->id);
    if(!$newsletter instanceof Newsletter) return $this->errorResponse();

    if (!empty($segments)) {
      NewsletterSegment::where('newsletter_id', $newsletter->id)
        ->deleteMany();
      foreach ($segments as $segment) {
        if (!is_array($segment)) continue;
        $relation = NewsletterSegment::create();
        $relation->segmentId = (int)$segment['id'];
        $relation->newsletterId = $newsletter->id;
        $relation->save();
      }
    }

    // save default sender if needed
    if (!$this->settings->get('sender') && !empty($data['sender_address']) && !empty($data['sender_name'])) {
      $this->settings->set('sender', [
        'address' => $data['sender_address'],
        'name' => $data['sender_name'],
      ]);
    }

    if (!empty($options)) {
      $optionFields = NewsletterOptionField::where(
        'newsletter_type',
        $newsletter->type
      )->findMany();
      // update newsletter options
      foreach ($optionFields as $optionField) {
        if (isset($options[$optionField->name])) {
          $newsletterOption = NewsletterOption::createOrUpdate(
            [
              'newsletter_id' => $newsletter->id,
              'option_field_id' => $optionField->id,
              'value' => $options[$optionField->name],
            ]
          );
        }
      }
      // reload newsletter with updated options
      $newsletter = Newsletter::filter('filterWithOptions', $newsletter->type)->findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      // if this is a post notification, process newsletter options and update its schedule
      if ($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        // generate the new schedule from options and get the new "next run" date
        $newsletter->schedule = $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
        $nextRunDate = Scheduler::getNextRunDate($newsletter->schedule);
        // find previously scheduled jobs and reschedule them using the new "next run" date
        SendingQueue::findTaskByNewsletterId($newsletter->id)
          ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $nextRunDate)
          ->save();
      }
    }

    $queue = $newsletter->getQueue();
    if ($queue && !in_array($newsletter->type, [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_NOTIFICATION_HISTORY])) {
      // if newsletter was previously scheduled and is now unscheduled, set its status to DRAFT and delete associated queue record
      if ($newsletter->status === Newsletter::STATUS_SCHEDULED && isset($options['isScheduled']) && empty($options['isScheduled'])) {
        $queue->delete();
        $newsletter->status = Newsletter::STATUS_DRAFT;
        $newsletter->save();
      } else {
        $queue->newsletterRenderedBody = null;
        $queue->newsletterRenderedSubject = null;
        $newsletterQueueTask = new NewsletterQueueTask();
        $newsletterQueueTask->preProcessNewsletter($newsletter, $queue);
      }
    }

    $this->wp->doAction('mailpoet_api_newsletters_save_after', $newsletter);
    $this->authorizedEmailsController->onNewsletterUpdate($newsletter, $oldNewsletter);

    $previewUrl = NewsletterUrl::getViewInBrowserUrl($newsletter);
    return $this->successResponse($newsletter->asArray(), ['preview_url' => $previewUrl]);
  }

  public function setStatus($data = []) {
    $status = (isset($data['status']) ? $data['status'] : null);

    if (!$status) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('You need to specify a status.', 'mailpoet'),
      ]);
    }

    if ($status === Newsletter::STATUS_ACTIVE && $this->subscribersFeature->check()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Subscribers limit reached.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOneWithOptions($id);

    if ($newsletter === false) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newsletter->setStatus($status);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) {
      return $this->errorResponse($errors);
    }

    // if there are past due notifications, reschedule them for the next send date
    if ($newsletter->type === Newsletter::TYPE_NOTIFICATION && $status === Newsletter::STATUS_ACTIVE) {
      $nextRunDate = Scheduler::getNextRunDate($newsletter->schedule);
      $queue = $newsletter->queue()->findOne();
      if ($queue) {
        $queue->task()
          ->whereLte('scheduled_at', Carbon::createFromTimestamp($this->wp->currentTime('timestamp')))
          ->where('status', SendingQueue::STATUS_SCHEDULED)
          ->findResultSet()
          ->set('scheduled_at', $nextRunDate)
          ->save();
      }
      $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    }

    $newsletter = Newsletter::findOne($newsletter->id);
    if(!$newsletter instanceof Newsletter) return $this->errorResponse();
    return $this->successResponse(
      $newsletter->asArray()
    );
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->restore();

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();

      return $this->successResponse(
        $newsletter->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->trash();

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      return $this->successResponse(
        $newsletter->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);
    if ($newsletter instanceof Newsletter) {
      $newsletter->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function duplicate($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $newsletter = Newsletter::findOne($id);

    if ($newsletter instanceof Newsletter) {
      $data = [
        'subject' => sprintf(__('Copy of %s', 'mailpoet'), $newsletter->subject),
      ];
      $duplicate = $newsletter->duplicate($data);
      $errors = $duplicate->getErrors();

      if (!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        $this->wp->doAction('mailpoet_api_newsletters_duplicate_after', $newsletter, $duplicate);
        $duplicate = Newsletter::findOne($duplicate->id);
        if(!$duplicate instanceof Newsletter) return $this->errorResponse();
        return $this->successResponse(
          $duplicate->asArray(),
          ['count' => 1]
        );
      }
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
      $this->emoji->encodeForUTF8Column(MP_NEWSLETTERS_TABLE, 'body', json_decode($data['body']))
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

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $newsletter = Newsletter::findOne($id);
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
    return $this->successResponse($newsletter->asArray());
  }

  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->newsletterListingRepository->getData($definition);
    $count = $this->newsletterListingRepository->getCount($definition);
    $filters = $this->newsletterListingRepository->getFilters($definition);
    $groups = $this->newsletterListingRepository->getGroups($definition);

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
    try {
      $meta = $this->bulkAction->apply('\MailPoet\Models\Newsletter', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  public function create($data = []) {
    $options = [];
    if (isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }

    $newsletter = Newsletter::createOrUpdate($data);
    $errors = $newsletter->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      // try to load template data
      $templateId = (isset($data['template']) ? (int)$data['template'] : false);
      $template = ContainerWrapper::getInstance()->get(NewsletterTemplatesRepository::class)->findOneById($templateId);
      if ($template) {
        $newsletter->body = json_encode($template->getBody());
      } else {
        $newsletter->body = [];
      }
    }

    $newsletter->save();
    $errors = $newsletter->getErrors();
    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      if (!empty($options)) {
        $optionFields = NewsletterOptionField::where(
          'newsletter_type', $newsletter->type
        )->findArray();

        foreach ($optionFields as $optionField) {
          if (isset($options[$optionField['name']])) {
            $relation = NewsletterOption::create();
            $relation->newsletterId = $newsletter->id;
            $relation->optionFieldId = $optionField['id'];
            $relation->value = $options[$optionField['name']];
            $relation->save();
          }
        }
      }

      if (
        empty($data['id'])
        &&
        isset($data['type'])
        &&
        $data['type'] === Newsletter::TYPE_NOTIFICATION
      ) {
        $newsletter = Newsletter::filter('filterWithOptions', $data['type'])->findOne($newsletter->id);
        $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
      }

      $newsletter = Newsletter::findOne($newsletter->id);
      if(!$newsletter instanceof Newsletter) return $this->errorResponse();
      return $this->successResponse(
        $newsletter->asArray()
      );
    }
  }

  /** @return NewsletterEntity|null */
  private function getNewsletter(array $data) {
    return isset($data['id'])
      ? $this->newslettersRepository->findOneById((int)$data['id'])
      : null;
  }

  private function getViewInBrowserUrl(NewsletterEntity $newsletter): string {
    $url = NewsletterUrl::getViewInBrowserUrl(
      (object)[
        'id' => $newsletter->getId(),
        'hash' => $newsletter->getHash(),
      ]
    );

    // strip protocol to avoid mix content error
    return preg_replace('/^https?:/i', '', $url);
  }
}
