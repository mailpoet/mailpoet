<?php

namespace MailPoet\Models;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

use function MailPoetVendor\array_column;

/**
 * @property int $id
 * @property int $parentId
 * @property string $type
 * @property object|array|bool $queue
 * @property string $hash
 * @property string $senderName
 * @property string $senderAddress
 * @property string $replyToName
 * @property string $replyToAddress
 * @property string $status
 * @property string|object $meta
 * @property array $options
 * @property int $childrenCount
 * @property bool|array $statistics
 * @property string $sentAt
 * @property string $deletedAt
 * @property int $totalSent
 * @property int $totalScheduled
 * @property array $segments
 * @property string $subject
 * @property string $preheader
 * @property string $body
 * @property string|null $schedule
 * @property bool|null $isScheduled
 * @property string|null $scheduledAt
 * @property string $gaCampaign
 */

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const TYPE_AUTOMATIC = NewsletterEntity::TYPE_AUTOMATIC;
  const TYPE_STANDARD = NewsletterEntity::TYPE_STANDARD;
  const TYPE_WELCOME = NewsletterEntity::TYPE_WELCOME;
  const TYPE_NOTIFICATION = NewsletterEntity::TYPE_NOTIFICATION;
  const TYPE_NOTIFICATION_HISTORY = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
  const TYPE_WC_TRANSACTIONAL_EMAIL = NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL;
  // standard newsletters
  const STATUS_DRAFT = NewsletterEntity::STATUS_DRAFT;
  const STATUS_SCHEDULED = NewsletterEntity::STATUS_SCHEDULED;
  const STATUS_SENDING = NewsletterEntity::STATUS_SENDING;
  const STATUS_SENT = NewsletterEntity::STATUS_SENT;
  // automatic newsletters status
  const STATUS_ACTIVE = NewsletterEntity::STATUS_ACTIVE;

  public function __construct() {
    parent::__construct();
    $this->addValidations('type', [
      'required' => WPFunctions::get()->__('Please specify a type.', 'mailpoet'),
    ]);
  }

  public function queue() {
    return $this->hasOne(__NAMESPACE__ . '\SendingQueue', 'newsletter_id', 'id');
  }

  public function children() {
    return $this->hasMany(
      __NAMESPACE__ . '\Newsletter',
      'parent_id',
      'id'
    );
  }

  public function parent() {
    return $this->hasOne(
      __NAMESPACE__ . '\Newsletter',
      'id',
      'parent_id'
    );
  }

  public function segments() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Segment',
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'segment_id'
    );
  }

  public function segmentRelations() {
    return $this->hasMany(
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'id'
    );
  }

  public function options() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\NewsletterOptionField',
      __NAMESPACE__ . '\NewsletterOption',
      'newsletter_id',
      'option_field_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE . '.value');
  }

  public function save() {
    if (is_string($this->deletedAt) && strlen(trim($this->deletedAt)) === 0) {
      $this->set_expr('deleted_at', 'NULL');
    }

    if (isset($this->body) && ($this->body !== false)) {
      if (is_array($this->body)) {
        $this->body = (string)json_encode($this->body);
      }
      $this->set(
        'body',
        $this->body
      );
    }

    $this->set('hash',
      ($this->hash)
      ? $this->hash
      : Security::generateHash()
    );
    return parent::save();
  }

  public function trash() {
    // trash queue associations
    $children = $this->children()->select('id')->findArray();
    if ($children) {
      $this->children()->rawExecute(
        'UPDATE `' . self::$_table . '` ' .
        'SET `deleted_at` = NOW() ' .
        'WHERE `parent_id` = ' . $this->id
      );
      ScheduledTask::rawExecute(
        'UPDATE `' . ScheduledTask::$_table . '` t ' .
        'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
        'SET t.`deleted_at` = NOW() ' .
        'WHERE q.`newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), [$this->id])) . ')'
      );
      SendingQueue::rawExecute(
        'UPDATE `' . SendingQueue::$_table . '` ' .
        'SET `deleted_at` = NOW() ' .
        'WHERE `newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), [$this->id])) . ')'
      );
    } else {
      ScheduledTask::rawExecute(
        'UPDATE `' . ScheduledTask::$_table . '` t ' .
        'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
        'SET t.`deleted_at` = NOW() ' .
        'WHERE q.`newsletter_id` = ' . $this->id
      );
      SendingQueue::rawExecute(
        'UPDATE `' . SendingQueue::$_table . '` ' .
        'SET `deleted_at` = NOW() ' .
        'WHERE `newsletter_id` = ' . $this->id
      );
    }

    return parent::trash();
  }

  public static function bulkTrash($orm) {
    // bulk trash queue and notification history associations
    parent::bulkAction($orm, function($ids) {
      $children = Newsletter::whereIn('parent_id', $ids)->select('id')->findArray();
      if ($children) {
        Newsletter::rawExecute(
          'UPDATE `' . Newsletter::$_table . '` ' .
          'SET `deleted_at` = NOW() ' .
          'WHERE `parent_id` IN (' . join(',', Helpers::flattenArray($ids)) . ')'
        );
        ScheduledTask::rawExecute(
          'UPDATE `' . ScheduledTask::$_table . '` t ' .
          'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
          'SET t.`deleted_at` = NOW() ' .
          'WHERE q.`newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), $ids)) . ')'
        );
        SendingQueue::rawExecute(
          'UPDATE `' . SendingQueue::$_table . '` ' .
          'SET `deleted_at` = NOW() ' .
          'WHERE `newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), $ids)) . ')'
        );
      } else {
        ScheduledTask::rawExecute(
          'UPDATE `' . ScheduledTask::$_table . '` t ' .
          'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
          'SET t.`deleted_at` = NOW() ' .
          'WHERE q.`newsletter_id` IN (' . join(',', Helpers::flattenArray($ids)) . ')'
        );
        SendingQueue::rawExecute(
          'UPDATE `' . SendingQueue::$_table . '` ' .
          'SET `deleted_at` = NOW() ' .
          'WHERE `newsletter_id` IN (' . join(',', Helpers::flattenArray($ids)) . ')'
        );
      }
    });

    return parent::bulkTrash($orm);
  }

  public function delete() {
    // delete queue, notification history and segment associations
    $children = $this->children()->select('id')->findArray();
    if ($children) {
      $children = Helpers::flattenArray($children);
      $this->children()->deleteMany();
      SendingQueue::getTasks()
        ->whereIn('queues.newsletter_id', array_merge($children, [$this->id]))
        ->findResultSet()
        ->delete();
      SendingQueue::whereIn('newsletter_id', array_merge($children, [$this->id]))->deleteMany();
      NewsletterSegment::whereIn('newsletter_id', array_merge($children, [$this->id]))->deleteMany();
    } else {
      SendingQueue::getTasks()
        ->where('queues.newsletter_id', $this->id)
        ->findResultSet()
        ->delete();
      $this->queue()->deleteMany();
      $this->segmentRelations()->deleteMany();
    }

    return parent::delete();
  }

  public static function bulkDelete($orm) {
    // bulk delete queue, notification history and segment associations
    parent::bulkAction($orm, function($ids) {
      $children = Newsletter::whereIn('parent_id', $ids)->select('id')->findArray();
      if ($children) {
        $children = Helpers::flattenArray($children);
        Newsletter::whereIn('parent_id', $ids)->deleteMany();
        SendingQueue::getTasks()
          ->whereIn('queues.newsletter_id', array_merge($children, $ids))
          ->findResultSet()
          ->delete();
        SendingQueue::whereIn('newsletter_id', array_merge($children, $ids))->deleteMany();
        NewsletterSegment::whereIn('newsletter_id', array_merge($children, $ids))->deleteMany();
      } else {
        SendingQueue::getTasks()
          ->whereIn('queues.newsletter_id', $ids)
          ->findResultSet()
          ->delete();
        SendingQueue::whereIn('newsletter_id', $ids)->deleteMany();
        NewsletterSegment::whereIn('newsletter_id', $ids)->deleteMany();
      }
    });

    return parent::bulkDelete($orm);
  }

  public function restore() {
    // restore trashed queue and notification history associations
    $children = $this->children()->select('id')->findArray();
    if ($children) {
      $this->children()->rawExecute(
        'UPDATE `' . self::$_table . '` ' .
        'SET `deleted_at` = null ' .
        'WHERE `parent_id` = ' . $this->id
      );
      ScheduledTask::rawExecute(
        'UPDATE `' . ScheduledTask::$_table . '` t ' .
        'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
        'SET t.`deleted_at` = null ' .
        'WHERE q.`newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), [$this->id])) . ')'
      );
      SendingQueue::rawExecute(
        'UPDATE `' . SendingQueue::$_table . '` ' .
        'SET `deleted_at` = null ' .
        'WHERE `newsletter_id` IN (' . join(',', array_merge(Helpers::flattenArray($children), [$this->id])) . ')'
      );
    } else {
      ScheduledTask::rawExecute(
        'UPDATE `' . ScheduledTask::$_table . '` t ' .
        'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
        'SET t.`deleted_at` = null ' .
        'WHERE q.`newsletter_id` = ' . $this->id
      );
      // Pause associated running scheduled task
      ScheduledTask::rawExecute(
        'UPDATE `' . ScheduledTask::$_table . '` t ' .
        'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
        'SET t.`status` = "' . ScheduledTaskEntity::STATUS_PAUSED . '" ' .
        'WHERE q.`newsletter_id` = ' . $this->id . ' AND t.`status` IS NULL'
      );
      SendingQueue::rawExecute(
        'UPDATE `' . SendingQueue::$_table . '` ' .
        'SET `deleted_at` = null ' .
        'WHERE `newsletter_id` = ' . $this->id
      );
    }

    return parent::restore();
  }

  public static function bulkRestore($orm) {
    // bulk restore trashed queue and notification history associations
    parent::bulkAction($orm, function($ids) {
      $children = Newsletter::whereIn('parent_id', $ids)->select('id')->findArray();
      if ($children) {
        Newsletter::whereIn('parent_id', $ids)
          ->whereNotNull('deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
        SendingQueue::getTasks()
          ->whereIn('queues.newsletter_id', Helpers::flattenArray($children))
          ->whereNotNull('tasks.deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
        SendingQueue::whereIn('newsletter_id', Helpers::flattenArray($children))
          ->whereNotNull('deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
      } else {
        SendingQueue::getTasks()
          ->whereIn('queues.newsletter_id', $ids)
          ->whereNotNull('tasks.deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
        // Pause associated running scheduled tasks
        SendingQueue::getTasks()
          ->whereIn('queues.newsletter_id', $ids)
          ->whereNull('tasks.status')
          ->findResultSet()
          ->set('status', ScheduledTaskEntity::STATUS_PAUSED)
          ->save();
        SendingQueue::whereIn('newsletter_id', $ids)
          ->whereNotNull('deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
      }
    });

    return parent::bulkRestore($orm);
  }

  public function setStatus($status = null) {
    if ($status === self::STATUS_ACTIVE) {
      if (!$this->body || empty(json_decode($this->body))) {
        $this->setError(
          Helpers::replaceLinkTags(
            __('This is an empty email without any content and it cannot be sent. Please update [link]the email[/link].'),
            'admin.php?page=mailpoet-newsletter-editor&id=' . $this->id
          )
        );
        return $this;
      }
    }
    if (in_array($status, [
      self::STATUS_DRAFT,
      self::STATUS_SCHEDULED,
      self::STATUS_SENDING,
      self::STATUS_SENT,
      self::STATUS_ACTIVE,
    ])) {
      $this->set('status', $status);
      $this->save();
    }

    $typesWithActivation = [self::TYPE_NOTIFICATION, self::TYPE_WELCOME, self::TYPE_AUTOMATIC];

    if (($status === self::STATUS_DRAFT) && in_array($this->type, $typesWithActivation)) {
      ScheduledTask::pauseAllByNewsletter($this);
    }
    if (($status === self::STATUS_ACTIVE) && in_array($this->type, $typesWithActivation)) {
      ScheduledTask::setScheduledAllByNewsletter($this);
    }
    return $this;
  }

  public function duplicate($data = []) {
    $newsletterData = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletterData['id']);

    // merge data with newsletter data (allows override)
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    $data = array_merge($newsletterData, $data);

    $duplicate = self::create();
    $duplicate->hydrate($data);

    // reset timestamps
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    $duplicate->set_expr('deleted_at', 'NULL');

    // reset status
    $duplicate->set('status', self::STATUS_DRAFT);

    // reset hash
    $duplicate->set('hash', null);

    // reset sent at date
    $duplicate->set('sent_at', null);

    $duplicate->save();

    if ($duplicate->getErrors() === false) {
      // create relationships between duplicate and segments
      $segments = $this->segments()->findMany();

      if (!empty($segments)) {
        foreach ($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segmentId = $segment->id;
          $relation->newsletterId = $duplicate->id;
          $relation->save();
        }
      }

      // duplicate options
      $options = NewsletterOption::where('newsletter_id', $this->id)
        ->findMany();

      $ignoredOptionFieldIds = Helpers::flattenArray(
        NewsletterOptionField::whereIn('name', ['isScheduled', 'scheduledAt'])
          ->select('id')
          ->findArray()
      );

      if (!empty($options)) {
        foreach ($options as $option) {
          if (in_array($option->optionFieldId, $ignoredOptionFieldIds)) {
            continue;
          }
          $relation = NewsletterOption::create();
          $relation->newsletterId = $duplicate->id;
          $relation->optionFieldId = $option->optionFieldId;
          $relation->value = $option->value;
          $relation->save();
        }
      }
    }

    return $duplicate;
  }

  public function createNotificationHistory() {
    $newsletterData = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletterData['id']);

    $data = array_merge(
      $newsletterData,
      [
        'parent_id' => $this->id,
        'type' => self::TYPE_NOTIFICATION_HISTORY,
        'status' => self::STATUS_SENDING,
        'unsubscribe_token' => Security::generateUnsubscribeToken(self::class),
      ]
    );

    $notificationHistory = self::create();
    $notificationHistory->hydrate($data);

    // reset timestamps
    $notificationHistory->set_expr('created_at', 'NOW()');
    $notificationHistory->set_expr('updated_at', 'NOW()');
    $notificationHistory->set_expr('deleted_at', 'NULL');

    // reset hash
    $notificationHistory->set('hash', null);

    $notificationHistory->save();

    if ($notificationHistory->getErrors() === false) {
      // create relationships between notification history and segments
      $segments = $this->segments()->findMany();

      if (!empty($segments)) {
        foreach ($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segmentId = $segment->id;
          $relation->newsletterId = $notificationHistory->id;
          $relation->save();
        }
      }
    }

    return $notificationHistory;
  }

  public function asArray() {
    $model = parent::asArray();

    if (isset($model['body'])) {
      $model['body'] = json_decode($model['body'], true);
    }
    return $model;
  }

  public function withSegments($inclDeleted = false) {
    $this->segments = $this->segments()->findArray();
    if ($inclDeleted) {
      $this->withDeletedSegments();
    }
    return $this;
  }

  public function withDeletedSegments() {
    if (!empty($this->segments)) {
      $segmentIds = array_column($this->segments, 'id');
      $links = $this->segmentRelations()
        ->whereNotIn('segment_id', $segmentIds)->findArray();
      $deletedSegments = [];

      foreach ($links as $link) {
        $deletedSegments[] = [
          'id' => $link['segment_id'],
          'name' => WPFunctions::get()->__('Deleted list', 'mailpoet'),
        ];
      }
      $this->segments = array_merge($this->segments, $deletedSegments);
    }

    return $this;
  }

  public function withChildrenCount() {
    $this->childrenCount = $this->children()->count();
    return $this;
  }

  public function getQueue($columns = '*') {
    return SendingTask::getByNewsletterId($this->id);
  }

  public function withSendingQueue() {
    $queue = $this->getQueue();
    if ($queue === false) {
      $this->queue = false;
    } else {
      $this->queue = $queue->asArray();
    }
    return $this;
  }

  public function withOptions() {
    $options = $this->options()->findArray();
    if (empty($options)) {
      $this->options = [];
    } else {
      $this->options = array_column($options, 'value', 'name');
    }
    return $this;
  }

  public function render() {
    $renderer = new Renderer($this);
    return $renderer->render();
  }

  public function wasScheduledForSubscriber($subscriberId) {
    /** @var \stdClass */
    $queue = SendingQueue::rawQuery(
      "SELECT COUNT(*) as count
      FROM `" . SendingQueue::$_table . "`
      JOIN `" . ScheduledTask::$_table . "` ON " . SendingQueue::$_table . ".task_id = " . ScheduledTask::$_table . ".id
      JOIN `" . ScheduledTaskSubscriber::$_table . "` ON " . ScheduledTask::$_table . ".id = " . ScheduledTaskSubscriber::$_table . ".task_id
      WHERE " . ScheduledTaskSubscriber::$_table . ".subscriber_id = " . $subscriberId . "
      AND " . SendingQueue::$_table . ".newsletter_id = " . $this->id
    )->findOne();

    return ((int)$queue->count) > 0;
  }

  public static function getAnalytics() {
    $welcomeNewslettersCount = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_WELCOME)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $notificationsCount = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_NOTIFICATION)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $automaticCount = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_AUTOMATIC)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $newslettersCount = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_STANDARD)
      ->filter('filterStatus', self::STATUS_SENT)
      ->count();

    $firstPurchaseEmailsCount = Newsletter::getPublished()
      ->filter('filterType', Newsletter::TYPE_AUTOMATIC, FirstPurchase::SLUG)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->count();

    $productPurchasedEmailsCount = Newsletter::getPublished()
      ->filter('filterType', Newsletter::TYPE_AUTOMATIC, PurchasedProduct::SLUG)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->count();

    $productPurchasedInCategoryEmailsCount = Newsletter::getPublished()
      ->filter('filterType', Newsletter::TYPE_AUTOMATIC, PurchasedInCategory::SLUG)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->count();

    $abandonedCartEmailsCount = Newsletter::getPublished()
      ->filter('filterType', Newsletter::TYPE_AUTOMATIC, AbandonedCart::SLUG)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->count();

    $sentNewsletters3Months = self::sentAfter(Carbon::now()->subMonths(3));
    $sentNewsletters30Days = self::sentAfter(Carbon::now()->subDays(30));

    return [
      'welcome_newsletters_count' => $welcomeNewslettersCount,
      'notifications_count' => $notificationsCount,
      'automatic_emails_count' => $automaticCount,
      'sent_newsletters_count' => $newslettersCount,
      'sent_newsletters_3_months' => $sentNewsletters3Months,
      'sent_newsletters_30_days' => $sentNewsletters30Days,
      'first_purchase_emails_count' => $firstPurchaseEmailsCount,
      'product_purchased_emails_count' => $productPurchasedEmailsCount,
      'product_purchased_in_category_emails_count' => $productPurchasedInCategoryEmailsCount,
      'abandoned_cart_emails_count' => $abandonedCartEmailsCount,
    ];
  }

  public static function sentAfter($date) {
    return static::tableAlias('newsletters')
      ->where('newsletters.type', self::TYPE_STANDARD)
      ->where('newsletters.status', self::STATUS_SENT)
      ->join(
        MP_SENDING_QUEUES_TABLE,
        'queues.newsletter_id = newsletters.id',
        'queues'
      )
      ->join(
        MP_SCHEDULED_TASKS_TABLE,
        'queues.task_id = tasks.id',
        'tasks'
      )
      ->where('tasks.status', SendingQueue::STATUS_COMPLETED)
      ->whereGte('tasks.processed_at', $date)
      ->count();
  }

  public static function search($orm, $search = '') {
    if (strlen(trim($search)) > 0) {
      $orm->whereLike('subject', '%' . $search . '%');
    }
    return $orm;
  }

  public static function filters($data = []) {
    $type = isset($data['params']['type']) ? $data['params']['type'] : null;
    $group = (isset($data['params']['group'])) ? $data['params']['group'] : null;

    // newsletter types without filters
    if (in_array($type, [
      self::TYPE_NOTIFICATION_HISTORY,
    ])) {
      return false;
    }

    $segments = Segment::orderByAsc('name')->findMany();
    $segmentList = [];
    $segmentList[] = [
      'label' => WPFunctions::get()->__('All Lists', 'mailpoet'),
      'value' => '',
    ];

    foreach ($segments as $segment) {
      $newsletters = $segment->newsletters()
        ->filter('filterType', $type, $group)
        ->filter('groupBy', $data);

      $newslettersCount = $newsletters->count();

      if ($newslettersCount > 0) {
        $segmentList[] = [
          'label' => sprintf('%s (%d)', $segment->name, $newslettersCount),
          'value' => $segment->id,
        ];
      }
    }

    $filters = [
      'segment' => $segmentList,
    ];

    return $filters;
  }

  public static function filterBy($orm, $data = []) {
    // apply filters
    if (!empty($data['filter'])) {
      foreach ($data['filter'] as $key => $value) {
        if ($key === 'segment') {
          $segment = Segment::findOne($value);
          if ($segment instanceof Segment) {
            $orm = $segment->newsletters();
          }
        }
      }
    }

    // filter by type
    $type = isset($data['params']['type']) ? $data['params']['type'] : null;
    if ($type !== null) {
      $group = (isset($data['params']['group'])) ? $data['params']['group'] : null;
      $orm->filter('filterType', $type, $group);
    }

    // filter by parent id
    $parentId = isset($data['params']['parent_id'])
      ? (int)$data['params']['parent_id']
      : null;
    if ($parentId !== null) {
      $orm->where('parent_id', $parentId);
    }

    return $orm;
  }

  public static function filterWithOptions($orm, $type) {
    $orm = $orm->select(MP_NEWSLETTERS_TABLE . '.*');
    $optionFields = NewsletterOptionField::findArray();
    foreach ($optionFields as $optionField) {
      if ($optionField['newsletter_type'] !== $type) {
        continue;
      }
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id=' . $optionField['id'] . ' THEN ' .
        MP_NEWSLETTER_OPTION_TABLE . '.value END), NULL) as "' . $optionField['name'] . '"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_TABLE,
        [
          MP_NEWSLETTERS_TABLE . '.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE . '.newsletter_id',
        ]
      )
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        [
          MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE . '.option_field_id',
        ]
      )
      ->group_by(MP_NEWSLETTERS_TABLE . '.id');
    return $orm;
  }

  public static function groups($data = []) {
    $type = isset($data['params']['type']) ? $data['params']['type'] : null;
    $group = (isset($data['params']['group'])) ? $data['params']['group'] : null;
    $parentId = (isset($data['params']['parent_id'])) ? $data['params']['parent_id'] : null;

    $getPublishedQuery = Newsletter::getPublished();
    if (!is_null($parentId)) {
      $getPublishedQuery->where('parent_id', $parentId);
    }
    $groups = [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => $getPublishedQuery
          ->filter('filterType', $type, $group)
          ->count(),
      ],
    ];

    switch ($type) {
      case self::TYPE_STANDARD:
        $groups = array_merge($groups, [
          [
            'name' => self::STATUS_DRAFT,
            'label' => WPFunctions::get()->__('Draft', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_DRAFT)
              ->count(),
          ],
          [
            'name' => self::STATUS_SCHEDULED,
            'label' => WPFunctions::get()->__('Scheduled', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_SCHEDULED)
              ->count(),
          ],
          [
            'name' => self::STATUS_SENDING,
            'label' => WPFunctions::get()->__('Sending', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_SENDING)
              ->count(),
          ],
          [
            'name' => self::STATUS_SENT,
            'label' => WPFunctions::get()->__('Sent', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_SENT)
              ->count(),
          ],
        ]);
        break;

      case self::TYPE_NOTIFICATION_HISTORY:
        $groups = array_merge($groups, [
          [
            'name' => self::STATUS_SENDING,
            'label' => WPFunctions::get()->__('Sending', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->where('parent_id', $parentId)
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_SENDING)
              ->count(),
          ],
          [
            'name' => self::STATUS_SENT,
            'label' => WPFunctions::get()->__('Sent', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->where('parent_id', $parentId)
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_SENT)
              ->count(),
          ],
        ]);
        break;

      case self::TYPE_WELCOME:
      case self::TYPE_NOTIFICATION:
      case self::TYPE_AUTOMATIC:
        $groups = array_merge($groups, [
          [
            'name' => self::STATUS_ACTIVE,
            'label' => WPFunctions::get()->__('Active', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_ACTIVE)
              ->count(),
          ],
          [
            'name' => self::STATUS_DRAFT,
            'label' => WPFunctions::get()->__('Not active', 'mailpoet'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type, $group)
              ->filter('filterStatus', self::STATUS_DRAFT)
              ->count(),
          ],
        ]);
        break;
    }

    $getTrashedQuery = Newsletter::getTrashed();
    if (!is_null($parentId)) {
      $getTrashedQuery->where('parent_id', $parentId);
    }
    $groups[] = [
      'name' => 'trash',
      'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
      'count' => $getTrashedQuery
        ->filter('filterType', $type, $group)
        ->count(),
    ];

    return $groups;
  }

  public static function groupBy($orm, $data = []) {
    $group = (!empty($data['group'])) ? $data['group'] : 'all';

    switch ($group) {
      case self::STATUS_DRAFT:
      case self::STATUS_SCHEDULED:
      case self::STATUS_SENDING:
      case self::STATUS_SENT:
      case self::STATUS_ACTIVE:
        $orm
          ->whereNull('deleted_at')
          ->filter('filterStatus', $group);
        break;

      case 'trash':
        $orm->whereNotNull('deleted_at');
        break;

      default:
        $orm->whereNull('deleted_at');
    }
    return $orm;
  }

  public static function filterStatus($orm, $status = false) {
    if (in_array($status, [
      self::STATUS_DRAFT,
      self::STATUS_SCHEDULED,
      self::STATUS_SENDING,
      self::STATUS_SENT,
      self::STATUS_ACTIVE,
    ])) {
      $orm->where('status', $status);
    }
    return $orm;
  }

  public static function filterType($orm, $type = false, $group = false) {
    if (in_array($type, [
      self::TYPE_STANDARD,
      self::TYPE_WELCOME,
      self::TYPE_AUTOMATIC,
      self::TYPE_NOTIFICATION,
      self::TYPE_NOTIFICATION_HISTORY,
    ])) {
      if ($type === self::TYPE_AUTOMATIC && $group) {
        $orm = $orm->join(
          NewsletterOptionField::$_table,
          [
            'option_fields.newsletter_type', '=', self::$_table . '.type',
          ],
          'option_fields'
        )
        ->join(
          NewsletterOption::$_table,
          [
            'options.newsletter_id', '=', self::$_table . '.id',
          ],
          'options'
        )
        ->whereRaw('`options`.`option_field_id` = `option_fields`.`id`')
        ->where('options.value', $group);
      }
      $orm = $orm->where(self::$_table . '.type', $type);
    }
    return $orm;
  }

  public static function listingQuery($data = []) {
    $query = self::select(
      [
        self::$_table . '.id',
        self::$_table . '.subject',
        self::$_table . '.hash',
        self::$_table . '.type',
        self::$_table . '.status',
        self::$_table . '.sent_at',
        self::$_table . '.updated_at',
        self::$_table . '.deleted_at',
      ]
    );
    if ($data['sort_by'] === 'sent_at') {
      $query = $query->orderByExpr('ISNULL(sent_at) DESC');
    }
    return $query
      ->filter('filterBy', $data)
      ->filter('groupBy', $data)
      ->filter('search', $data['search']);
  }

  public static function createOrUpdate($data = []) {
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    return parent::_createOrUpdate($data, false, function($data) {
      $settings = SettingsController::getInstance();
      // set default sender based on settings
      if (empty($data['sender'])) {
        $sender = $settings->get('sender', []);
        $data['sender_name'] = (
          !empty($sender['name'])
          ? $sender['name']
          : ''
        );
        $data['sender_address'] = (
          !empty($sender['address'])
          ? $sender['address']
          : ''
        );
      }

      // set default reply_to based on settings
      if (empty($data['reply_to'])) {
        $replyTo = $settings->get('reply_to', []);
        $data['reply_to_name'] = (
          !empty($replyTo['name'])
          ? $replyTo['name']
          : ''
        );
        $data['reply_to_address'] = (
          !empty($replyTo['address'])
          ? $replyTo['address']
          : ''
        );
      }

      return $data;
    });
  }

  public static function getWelcomeNotificationsForSegments($segments) {
    return NewsletterOption::tableAlias('options')
      ->select('options.newsletter_id')
      ->select('options.value', 'segment_id')
      ->join(
        self::$_table,
        'newsletters.id = options.newsletter_id',
        'newsletters'
      )
      ->join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        'option_fields.id = options.option_field_id',
        'option_fields'
      )
      ->whereNull('newsletters.deleted_at')
      ->where('newsletters.type', 'welcome')
      ->where('option_fields.name', 'segment')
      ->whereIn('options.value', $segments)
      ->findMany();
  }

  public static function getArchives($segmentIds = []) {
    $orm = self::tableAlias('newsletters')
      ->distinct()->select('newsletters.*')
      ->select('newsletter_rendered_subject')
      ->whereIn('newsletters.type', [
        self::TYPE_STANDARD,
        self::TYPE_NOTIFICATION_HISTORY,
      ])
      ->join(
        MP_SENDING_QUEUES_TABLE,
        'queues.newsletter_id = newsletters.id',
        'queues'
      )
      ->join(
        MP_SCHEDULED_TASKS_TABLE,
        'queues.task_id = tasks.id',
        'tasks'
      )
      ->where('tasks.status', SendingQueue::STATUS_COMPLETED)
      ->whereNull('newsletters.deleted_at')
      ->select('tasks.processed_at')
      ->orderByDesc('tasks.processed_at')
      ->orderByAsc('tasks.id');

    if (!empty($segmentIds)) {
      $orm->join(
        MP_NEWSLETTER_SEGMENT_TABLE,
        'newsletter_segments.newsletter_id = newsletters.id',
        'newsletter_segments'
      )
      ->whereIn('newsletter_segments.segment_id', $segmentIds);
    }
    return $orm->findMany();
  }

  public static function getByHash($hash) {
    return parent::where('hash', $hash)
      ->findOne();
  }

  public function getMeta() {
    if (!$this->meta) return;

    return (Helpers::isJson($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  public static function findOneWithOptions($id) {
    $newsletter = self::findOne($id);
    if (!$newsletter instanceof self) {
      return false;
    }
    return self::filter('filterWithOptions', $newsletter->type)->findOne($id);
  }
}
