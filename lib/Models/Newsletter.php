<?php
namespace MailPoet\Models;
use Carbon\Carbon;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Emoji;
use function MailPoet\Util\array_column;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property int $id
 * @property string $type
 * @property object|boolean $queue
 * @property string $hash
 * @property string $sender_address
 * @property string $status
 * @property string|object $meta
 * @property array $options
 * @property int $children_count
 * @property bool|array $statistics
 * @property string $deleted_at
 * @property int $total_sent
 * @property int $total_scheduled
 * @property array $segments
 * @property string $subject
 * @property string $body
 * @property string|null $schedule
 * @property boolean|null $isScheduled
 * @property string|null $scheduledAt
 */

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;
  const TYPE_AUTOMATIC = 'automatic';
  const TYPE_STANDARD = 'standard';
  const TYPE_WELCOME = 'welcome';
  const TYPE_NOTIFICATION = 'notification';
  const TYPE_NOTIFICATION_HISTORY = 'notification_history';
  // standard newsletters
  const STATUS_DRAFT = 'draft';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_SENDING = 'sending';
  const STATUS_SENT = 'sent';
  // automatic newsletters status
  const STATUS_ACTIVE = 'active';

  private $emoji;

  function __construct() {
    parent::__construct();
    $this->addValidations('type', [
      'required' => WPFunctions::get()->__('Please specify a type.', 'mailpoet'),
    ]);
    $this->emoji = new Emoji();
  }

  function queue() {
    return $this->hasOne(__NAMESPACE__ . '\SendingQueue', 'newsletter_id', 'id');
  }

  function children() {
    return $this->hasMany(
      __NAMESPACE__ . '\Newsletter',
      'parent_id',
      'id'
    );
  }

  function parent() {
    return $this->hasOne(
      __NAMESPACE__ . '\Newsletter',
      'id',
      'parent_id'
    );
  }

  function segments() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Segment',
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'segment_id'
    );
  }

  function segmentRelations() {
    return $this->hasMany(
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'id'
    );
  }

  function options() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\NewsletterOptionField',
      __NAMESPACE__ . '\NewsletterOption',
      'newsletter_id',
      'option_field_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE . '.value');
  }

  function save() {
    if (is_string($this->deleted_at) && strlen(trim($this->deleted_at)) === 0) {
      $this->set_expr('deleted_at', 'NULL');
    }

    if (isset($this->body)) {
      if (is_array($this->body)) {
        $this->body = json_encode($this->body);
      }
      $this->set(
        'body',
        $this->emoji->encodeForUTF8Column(self::$_table, 'body', $this->body)
      );
    }

    $this->set('hash',
      ($this->hash)
      ? $this->hash
      : Security::generateHash()
    );
    return parent::save();
  }

  function trash() {
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

  static function bulkTrash($orm) {
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

  function delete() {
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

  static function bulkDelete($orm) {
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

  function restore() {
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
      SendingQueue::rawExecute(
        'UPDATE `' . SendingQueue::$_table . '` ' .
        'SET `deleted_at` = null ' .
        'WHERE `newsletter_id` = ' . $this->id
      );
    }

    if ($this->status == self::STATUS_SENDING) {
      $this->set('status', self::STATUS_DRAFT);
      $this->save();
    }
    return parent::restore();
  }

  static function bulkRestore($orm) {
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
        SendingQueue::whereIn('newsletter_id', $ids)
          ->whereNotNull('deleted_at')
          ->findResultSet()
          ->set('deleted_at', null)
          ->save();
      }
    });

    parent::bulkAction($orm, function($ids) {
      Newsletter::whereIn('id', $ids)
        ->where('status', Newsletter::STATUS_SENDING)
        ->findResultSet()
        ->set('status', Newsletter::STATUS_DRAFT)
        ->save();
    });

    return parent::bulkRestore($orm);
  }

  function setStatus($status = null) {
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

    $types_with_activation = [self::TYPE_NOTIFICATION, self::TYPE_WELCOME, self::TYPE_AUTOMATIC];

    if (($status === self::STATUS_DRAFT) && in_array($this->type, $types_with_activation)) {
      ScheduledTask::pauseAllByNewsletter($this);
    }
    if (($status === self::STATUS_ACTIVE) && in_array($this->type, $types_with_activation)) {
      ScheduledTask::setScheduledAllByNewsletter($this);
    }
    return $this;
  }

  function duplicate($data = []) {
    $newsletter_data = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletter_data['id']);

    // merge data with newsletter data (allows override)
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    $data = array_merge($newsletter_data, $data);

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
          $relation->segment_id = $segment->id;
          $relation->newsletter_id = $duplicate->id;
          $relation->save();
        }
      }

      // duplicate options
      $options = NewsletterOption::where('newsletter_id', $this->id)
        ->findMany();

      $ignored_option_field_ids = Helpers::flattenArray(
        NewsletterOptionField::whereIn('name', ['isScheduled', 'scheduledAt'])
          ->select('id')
          ->findArray()
      );

      if (!empty($options)) {
        foreach ($options as $option) {
          if (in_array($option->option_field_id, $ignored_option_field_ids)) {
            continue;
          }
          $relation = NewsletterOption::create();
          $relation->newsletter_id = $duplicate->id;
          $relation->option_field_id = $option->option_field_id;
          $relation->value = $option->value;
          $relation->save();
        }
      }
    }

    return $duplicate;
  }

  function createNotificationHistory() {
    $newsletter_data = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletter_data['id']);

    $data = array_merge(
      $newsletter_data,
      [
        'parent_id' => $this->id,
        'type' => self::TYPE_NOTIFICATION_HISTORY,
        'status' => self::STATUS_SENDING,
        'unsubscribe_token' => Security::generateUnsubscribeToken(self::class),
      ]
    );

    $notification_history = self::create();
    $notification_history->hydrate($data);

    // reset timestamps
    $notification_history->set_expr('created_at', 'NOW()');
    $notification_history->set_expr('updated_at', 'NOW()');
    $notification_history->set_expr('deleted_at', 'NULL');

    // reset hash
    $notification_history->set('hash', null);

    $notification_history->save();

    if ($notification_history->getErrors() === false) {
      // create relationships between notification history and segments
      $segments = $this->segments()->findMany();

      if (!empty($segments)) {
        foreach ($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segment_id = $segment->id;
          $relation->newsletter_id = $notification_history->id;
          $relation->save();
        }
      }
    }

    return $notification_history;
  }

  function asArray() {
    $model = parent::asArray();

    if (isset($model['body'])) {
      $model['body'] = json_decode($model['body'], true);
    }
    return $model;
  }

  function withSegments($incl_deleted = false) {
    $this->segments = $this->segments()->findArray();
    if ($incl_deleted) {
      $this->withDeletedSegments();
    }
    return $this;
  }

  function withDeletedSegments() {
    if (!empty($this->segments)) {
      $segment_ids = array_column($this->segments, 'id');
      $links = $this->segmentRelations()
        ->whereNotIn('segment_id', $segment_ids)->findArray();
      $deleted_segments = [];

      foreach ($links as $link) {
        $deleted_segments[] = [
          'id' => $link['segment_id'],
          'name' => WPFunctions::get()->__('Deleted list', 'mailpoet'),
        ];
      }
      $this->segments = array_merge($this->segments, $deleted_segments);
    }

    return $this;
  }

  function withChildrenCount() {
    $this->children_count = $this->children()->count();
    return $this;
  }

  function getQueue($columns = '*') {
    return SendingTask::getByNewsletterId($this->id);
  }

  function withSendingQueue() {
    $queue = $this->getQueue();
    if ($queue === false) {
      $this->queue = false;
    } else {
      $this->queue = $queue->asArray();
    }
    return $this;
  }

  function withOptions() {
    $options = $this->options()->findArray();
    if (empty($options)) {
      $this->options = [];
    } else {
      $this->options = array_column($options, 'value', 'name');
    }
    return $this;
  }

  function withTotalSent() {
    // total of subscribers who received the email
    $this->total_sent = (int)SendingQueue::findTaskByNewsletterId($this->id)
      ->where('tasks.status', SendingQueue::STATUS_COMPLETED)
      ->sum('queues.count_processed');
    return $this;
  }

  function withScheduledToBeSent() {
    $this->total_scheduled = (int)SendingQueue::findTaskByNewsletterId($this->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->count();
    return $this;
  }

  function withStatistics(WCHelper $woocommerce_helper) {
    $statistics = $this->getStatistics($woocommerce_helper);
    $this->statistics = $statistics;
    return $this;
  }

  function render() {
    $renderer = new Renderer($this);
    return $renderer->render();
  }

  function getStatistics(WCHelper $woocommerce_helper) {
    if (($this->type !== self::TYPE_WELCOME) && ($this->queue === false)) {
      return false;
    }

    $statisticsExprs = [
      'clicked' => StatisticsClicks::selectExpr('COUNT(DISTINCT subscriber_id) as cnt')->tableAlias("stat"),
      'opened' => StatisticsOpens::selectExpr('COUNT(DISTINCT subscriber_id) as cnt')->tableAlias("stat"),
      'unsubscribed' => StatisticsUnsubscribes::selectExpr('COUNT(DISTINCT subscriber_id) as cnt')->tableAlias("stat"),
    ];
    $result = [];

    foreach ($statisticsExprs as $name => $statisticsExpr) {
      if (!in_array($this->type, [self::TYPE_WELCOME, self::TYPE_AUTOMATIC])) {
        $row = $statisticsExpr->whereRaw('`queue_id` = ?', [$this->queue['id']])->findOne();
      } else {
        $row = $statisticsExpr
          ->join(MP_SENDING_QUEUES_TABLE, ["queue_id", "=", "qt.id"], "qt")
          ->join(MP_SCHEDULED_TASKS_TABLE, ["qt.task_id", "=", "tasks.id"], "tasks")
          ->where([
            "tasks.status" => SendingQueue::STATUS_COMPLETED,
            "stat.newsletter_id" => $this->id,
          ])->findOne();
      }

      $result[$name] = !empty($row->cnt) ? (int)$row->cnt : 0;
    }

    // WooCommerce revenues
    if ($woocommerce_helper->isWooCommerceActive()) {
      $currency = $woocommerce_helper->getWoocommerceCurrency();
      $row = StatisticsWooCommercePurchases::selectExpr('SUM(order_price_total) AS total')
        ->selectExpr('count(*)', 'count')
        ->where([
          'newsletter_id' => $this->id,
          'order_currency' => $currency,
        ])
        ->findOne();

      $revenue = !empty($row->total) ? (float)$row->total : 0.0;
      $count = !empty($row->count) ? (int)$row->count : 0;
      $result['revenue'] = [
        'currency' => $currency,
        'value' => $revenue,
        'count' => $count,
        'formatted' => $woocommerce_helper->getRawPrice($revenue, ['currency' => $currency]),
      ];
    } else {
      $result['revenue'] = null;
    }

    return $result;
  }

  function wasScheduledForSubscriber($subscriber_id) {
    /** @var \stdClass */
    $queue = SendingQueue::rawQuery(
      "SELECT COUNT(*) as count
      FROM `" . SendingQueue::$_table . "`
      JOIN `" . ScheduledTask::$_table . "` ON " . SendingQueue::$_table . ".task_id = " . ScheduledTask::$_table . ".id
      JOIN `" . ScheduledTaskSubscriber::$_table . "` ON " . ScheduledTask::$_table . ".id = " . ScheduledTaskSubscriber::$_table . ".task_id
      WHERE " . ScheduledTaskSubscriber::$_table . ".subscriber_id = " . $subscriber_id . "
      AND " . SendingQueue::$_table . ".newsletter_id = " . $this->id
    )->findOne();

    return ((int)$queue->count) > 0;
  }


  static function getAnalytics() {
    $welcome_newsletters_count = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_WELCOME)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $notifications_count = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_NOTIFICATION)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $automatic_count = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_AUTOMATIC)
      ->filter('filterStatus', self::STATUS_ACTIVE)
      ->count();

    $newsletters_count = Newsletter::getPublished()
      ->filter('filterType', self::TYPE_STANDARD)
      ->filter('filterStatus', self::STATUS_SENT)
      ->count();

    $first_purchase_emails_count = self::getActiveAutomaticNewslettersCount('woocommerce_first_purchase');
    $product_purchased_emails_count = self::getActiveAutomaticNewslettersCount('woocommerce_product_purchased');

    $sent_newsletters_3_months = self::sentAfter(Carbon::now()->subMonths(3));
    $sent_newsletters_30_days = self::sentAfter(Carbon::now()->subDays(30));

    return [
      'welcome_newsletters_count' => $welcome_newsletters_count,
      'notifications_count' => $notifications_count,
      'automatic_emails_count' => $automatic_count,
      'sent_newsletters_count' => $newsletters_count,
      'sent_newsletters_3_months' => $sent_newsletters_3_months,
      'sent_newsletters_30_days' => $sent_newsletters_30_days,
      'first_purchase_emails_count' => $first_purchase_emails_count,
      'product_purchased_emails_count' => $product_purchased_emails_count,
    ];
  }

  private static function getActiveAutomaticNewslettersCount($event_name) {
    return NewsletterOption::tableAlias('options')
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
      ->where('newsletters.type', self::TYPE_AUTOMATIC)
      ->where('newsletters.status', self::STATUS_ACTIVE)
      ->where('option_fields.name', 'event')
      ->where('options.value', $event_name)
      ->count();
  }

  static function sentAfter($date) {
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

  static function search($orm, $search = '') {
    if (strlen(trim($search)) > 0) {
      $orm->whereLike('subject', '%' . $search . '%');
    }
    return $orm;
  }

  static function filters($data = []) {
    $type = isset($data['params']['type']) ? $data['params']['type'] : null;
    $group = (isset($data['params']['group'])) ? $data['params']['group'] : null;

    // newsletter types without filters
    if (in_array($type, [
      self::TYPE_NOTIFICATION_HISTORY,
    ])) {
      return false;
    }

    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = [];
    $segment_list[] = [
      'label' => WPFunctions::get()->__('All Lists', 'mailpoet'),
      'value' => '',
    ];

    foreach ($segments as $segment) {
      $newsletters = $segment->newsletters()
        ->filter('filterType', $type, $group)
        ->filter('groupBy', $data);

      $newsletters_count = $newsletters->count();

      if ($newsletters_count > 0) {
        $segment_list[] = [
          'label' => sprintf('%s (%d)', $segment->name, $newsletters_count),
          'value' => $segment->id,
        ];
      }
    }

    $filters = [
      'segment' => $segment_list,
    ];

    return $filters;
  }

  static function filterBy($orm, $data = []) {
    // apply filters
    if (!empty($data['filter'])) {
      foreach ($data['filter'] as $key => $value) {
        if ($key === 'segment') {
          $segment = Segment::findOne($value);
          if ($segment !== false) {
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
    $parent_id = isset($data['params']['parent_id'])
      ? (int)$data['params']['parent_id']
      : null;
    if ($parent_id !== null) {
      $orm->where('parent_id', $parent_id);
    }

    return $orm;
  }

  static function filterWithOptions($orm, $type) {
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

  static function groups($data = []) {
    $type = isset($data['params']['type']) ? $data['params']['type'] : null;
    $group = (isset($data['params']['group'])) ? $data['params']['group'] : null;

    // newsletter types without groups
    if (in_array($type, [
      self::TYPE_NOTIFICATION_HISTORY,
    ])) {
      return false;
    }

    $groups = [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => Newsletter::getPublished()
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

    $groups[] = [
      'name' => 'trash',
      'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
      'count' => Newsletter::getTrashed()
        ->filter('filterType', $type, $group)
        ->count(),
    ];

    return $groups;
  }

  static function groupBy($orm, $data = []) {
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

  static function filterStatus($orm, $status = false) {
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

  static function filterType($orm, $type = false, $group = false) {
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

  static function listingQuery($data = []) {
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

  static function createOrUpdate($data = []) {
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    return parent::_createOrUpdate($data, false, function($data) {
      $settings = new SettingsController();
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
        $reply_to = $settings->get('reply_to', []);
        $data['reply_to_name'] = (
          !empty($reply_to['name'])
          ? $reply_to['name']
          : ''
        );
        $data['reply_to_address'] = (
          !empty($reply_to['address'])
          ? $reply_to['address']
          : ''
        );
      }

      return $data;
    });
  }

  static function getWelcomeNotificationsForSegments($segments) {
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

  static function getArchives($segment_ids = []) {
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
      ->orderByDesc('tasks.processed_at');

    if (!empty($segment_ids)) {
      $orm->join(
        MP_NEWSLETTER_SEGMENT_TABLE,
        'newsletter_segments.newsletter_id = newsletters.id',
        'newsletter_segments'
      )
      ->whereIn('newsletter_segments.segment_id', $segment_ids);
    }
    return $orm->findMany();
  }

  static function getByHash($hash) {
    return parent::where('hash', $hash)
      ->findOne();
  }

  function getMeta() {
    if (!$this->meta) return;

    return (Helpers::isJson($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  static function findOneWithOptions($id) {
    $newsletter = self::findOne($id);
    if ($newsletter === false) {
      return false;
    }
    return self::filter('filterWithOptions', $newsletter->type)->findOne($id);
  }
}
