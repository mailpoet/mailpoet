<?php
namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use function MailPoet\Util\array_column;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Util\Security;

if (!defined('ABSPATH')) exit;

/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $status
 * @property string|null $subscribed_ip
 * @property string|null $confirmed_ip
 * @property string|null $confirmed_at
 * @property string|null $last_subscribed_at
 * @property string|null $deleted_at
 * @property string|null $source
 * @property int $count_confirmations
 * @property int $wp_user_id
 * @property array $segments
 * @property array $subscriptions
 * @property string $unconfirmed_data
 * @property int $is_woocommerce_user
 */

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';
  const STATUS_BOUNCED = 'bounced';
  const STATUS_INACTIVE = 'inactive';

  /** @var string|bool */
  public $token;

  function __construct() {
    parent::__construct();

    $this->addValidations('email', [
      'required' => WPFunctions::get()->__('Please enter your email address', 'mailpoet'),
      'validEmail' => WPFunctions::get()->__('Your email address is invalid!', 'mailpoet'),
    ]);
  }

  static function findOne($id = false) {
    if (is_int($id) || (string)(int)$id === $id) {
      return parent::findOne($id);
    } else if (strlen(trim($id)) > 0) {
      return parent::where('email', $id)->findOne();
    }
    return false;
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Segment',
      __NAMESPACE__ . '\SubscriberSegment',
      'subscriber_id',
      'segment_id'
    )
    ->where(MP_SUBSCRIBER_SEGMENT_TABLE . '.status', self::STATUS_SUBSCRIBED);
  }

  function save() {
    // convert email to lowercase format
    $this->email = strtolower($this->email);
    return parent::save();
  }

  function delete() {
    // WP Users cannot be deleted
    if (!$this->isWPUser() && !$this->isWooCommerceUser()) {
      // delete all relations to segments
      SubscriberSegment::deleteSubscriptions($this);
      // delete all relations to custom fields
      SubscriberCustomField::deleteSubscriberRelations($this);
      return parent::delete();
    }
  }

  function trash() {
    // WP Users cannot be trashed
    if ($this->isWPUser() || $this->isWooCommerceUser()) {
      return false;
    } else {
      return parent::trash();
    }
  }

  function isWPUser() {
    return ($this->wp_user_id !== null);
  }

  function isWooCommerceUser() {
    return (bool)$this->is_woocommerce_user;
  }

  static function getCurrentWPUser() {
    $wp_user = WPFunctions::get()->wpGetCurrentUser();
    if (empty($wp_user->ID)) {
      return false; // Don't look up a subscriber for guests
    }
    return self::where('wp_user_id', $wp_user->ID)->findOne();
  }

  static function generateToken($email = null, $length = 32) {
    if ($email !== null) {
      $auth_key = '';
      if (defined('AUTH_KEY')) {
        $auth_key = AUTH_KEY;
      }
      return substr(md5($auth_key . $email), 0, $length);
    }
    return false;
  }

  static function verifyToken($email, $token) {
    return call_user_func(
      'hash_equals',
      self::generateToken($email, strlen($token)),
      $token
    );
  }

  static function filterOutReservedColumns(array $subscriber_data) {
    $reserved_columns = [
      'id',
      'wp_user_id',
      'is_woocommerce_user',
      'status',
      'subscribed_ip',
      'confirmed_ip',
      'confirmed_at',
      'created_at',
      'updated_at',
      'deleted_at',
      'unconfirmed_data',
    ];
    $subscriber_data = array_diff_key(
      $subscriber_data,
      array_flip($reserved_columns)
    );
    return $subscriber_data;
  }

  static function search($orm, $search = '') {
    if (strlen(trim($search)) === 0) {
      return $orm;
    }

    return $orm->where_raw(
      '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
      ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']
    );
  }

  static function filters($data = []) {
    $group = (!empty($data['group'])) ? $data['group'] : 'all';

    $segments = Segment::orderByAsc('name')
      ->whereNull('deleted_at')
      ->whereIn('type', Segment::getSegmentTypes())
      ->findMany();
    $segment_list = [];
    $segment_list[] = [
      'label' => WPFunctions::get()->__('All Lists', 'mailpoet'),
      'value' => '',
    ];

    $subscribers_without_segment = self::filter('withoutSegments')
      ->whereNull('deleted_at')
      ->count();
    $subscribers_without_segment_label = sprintf(
      WPFunctions::get()->__('Subscribers without a list (%s)', 'mailpoet'),
      number_format($subscribers_without_segment)
    );

    $segment_list[] = [
      'label' => $subscribers_without_segment_label,
      'value' => 'none',
    ];

    foreach ($segments as $segment) {
      $subscribers_count = $segment->subscribers()
        ->filter('groupBy', $group)
        ->count();

      $label = sprintf(
        '%s (%s)',
        $segment->name,
        number_format($subscribers_count)
      );

      $segment_list[] = [
        'label' => $label,
        'value' => $segment->id(),
      ];
    }

    $filters = [
      'segment' => $segment_list,
    ];

    return $filters;
  }

  static function filterBy($orm, $filters = null) {
    if (empty($filters)) {
      return $orm;
    }
    foreach ($filters as $key => $value) {
      if ($key === 'segment') {
        if ($value === 'none') {
          return self::filter('withoutSegments');
        } else {
          $segment = Segment::findOne($value);
          if ($segment instanceof Segment) {
            return $segment->subscribers();
          }
        }
      }
    }
    return $orm;
  }

  static function groups() {
    return [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => self::getPublished()->count(),
      ],
      [
        'name' => self::STATUS_SUBSCRIBED,
        'label' => WPFunctions::get()->__('Subscribed', 'mailpoet'),
        'count' => self::filter(self::STATUS_SUBSCRIBED)->count(),
      ],
      [
        'name' => self::STATUS_UNCONFIRMED,
        'label' => WPFunctions::get()->__('Unconfirmed', 'mailpoet'),
        'count' => self::filter(self::STATUS_UNCONFIRMED)->count(),
      ],
      [
        'name' => self::STATUS_UNSUBSCRIBED,
        'label' => WPFunctions::get()->__('Unsubscribed', 'mailpoet'),
        'count' => self::filter(self::STATUS_UNSUBSCRIBED)->count(),
      ],
      [
        'name' => self::STATUS_INACTIVE,
        'label' => WPFunctions::get()->__('Inactive', 'mailpoet'),
        'count' => self::filter(self::STATUS_INACTIVE)->count(),
      ],
      [
        'name' => self::STATUS_BOUNCED,
        'label' => WPFunctions::get()->__('Bounced', 'mailpoet'),
        'count' => self::filter(self::STATUS_BOUNCED)->count(),
      ],
      [
        'name' => 'trash',
        'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
        'count' => self::getTrashed()->count(),
      ],
    ];
  }

  static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    } else if ($group === 'all') {
      return $orm->whereNull('deleted_at');
    } else {
      return $orm->filter($group);
    }
  }

  static function filterWithCustomFields($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE . '.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END), NULL) as "' . $customField['name'] . '"');
    }
    $orm = $orm
      ->leftOuterJoin(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        [
          MP_SUBSCRIBERS_TABLE . '.id',
          '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.subscriber_id',
        ]
      )
      ->leftOuterJoin(
        MP_CUSTOM_FIELDS_TABLE,
        [
          MP_CUSTOM_FIELDS_TABLE . '.id',
          '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.custom_field_id',
        ]
      )
      ->groupBy(MP_SUBSCRIBERS_TABLE . '.id');
    return $orm;
  }

  static function filterWithCustomFieldsForExport($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE . '.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->selectExpr(
        'MAX(CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END) as "' . $customField['id'] . '"'
      );
    }
    $orm = $orm
      ->leftOuterJoin(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        [
          MP_SUBSCRIBERS_TABLE . '.id', '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.subscriber_id',
        ]
      )
      ->leftOuterJoin(
        MP_CUSTOM_FIELDS_TABLE,
        [
          MP_CUSTOM_FIELDS_TABLE . '.id','=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.custom_field_id',
        ]
      );
    return $orm;
  }

  static function getSubscribedInSegments($segment_ids) {
    $subscribers = SubscriberSegment::tableAlias('relation')
      ->whereIn('relation.segment_id', $segment_ids)
      ->where('relation.status', 'subscribed')
      ->join(
        MP_SUBSCRIBERS_TABLE,
        'subscribers.id = relation.subscriber_id',
        'subscribers'
      )
      ->select('subscribers.id')
      ->whereNull('subscribers.deleted_at')
      ->where('subscribers.status', 'subscribed')
      ->distinct();
    return $subscribers;
  }

  function customFields() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\CustomField',
      __NAMESPACE__ . '\SubscriberCustomField',
      'subscriber_id',
      'custom_field_id'
    )->select_expr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value');
  }

  static function createOrUpdate($data = []) {
    $subscriber = false;
    if (is_array($data) && !empty($data)) {
      $data = WPFunctions::get()->stripslashesDeep($data);
    }

    if (isset($data['id']) && (int)$data['id'] > 0) {
      $subscriber = self::findOne((int)$data['id']);
      unset($data['id']);
    }

    if ($subscriber === false && !empty($data['email'])) {
      $subscriber = self::where('email', $data['email'])->findOne();
      if ($subscriber !== false) {
        unset($data['email']);
      }
    }

    // segments
    $segment_ids = false;
    if (array_key_exists('segments', $data)) {
      $segment_ids = (array)$data['segments'];
      unset($data['segments']);
    }

    // if new subscriber, make sure that required fields are set
    if (!$subscriber) {
      $data = self::setRequiredFieldsDefaultValues($data);
    }

    // get custom fields
    list($data, $custom_fields) = self::extractCustomFieldsFromFromObject($data);

    // wipe any unconfirmed data at this point
    $data['unconfirmed_data'] = null;

    $old_status = false;
    $new_status = false;

    if ($subscriber === false) {
      $subscriber = self::create();
      $subscriber->hydrate($data);
    } else {
      $old_status = $subscriber->status;
      $subscriber->set($data);
      $new_status = $subscriber->status;
    }

    // Update last_subscribed_at when status changes to subscribed
    if ($old_status !== self::STATUS_SUBSCRIBED && $subscriber->status === self::STATUS_SUBSCRIBED) {
      $subscriber->set('last_subscribed_at', WPFunctions::get()->currentTime('mysql'));
    }

    if ($subscriber->save()) {
      if (!empty($custom_fields)) {
        $subscriber->saveCustomFields($custom_fields);
      }

      // check for status change
      if (
          ($old_status === self::STATUS_SUBSCRIBED)
          &&
          ($new_status === self::STATUS_UNSUBSCRIBED)
      ) {
        // make sure we unsubscribe the user from all segments
        SubscriberSegment::unsubscribeFromSegments($subscriber);
      } else {
        if ($segment_ids !== false) {
          SubscriberSegment::resetSubscriptions($subscriber, $segment_ids);
        }
      }
    }
    return $subscriber;
  }

  function withCustomFields() {
    $custom_fields = CustomField::select('id')->findArray();
    if (empty($custom_fields)) return $this;

    $custom_field_ids = array_column($custom_fields, 'id');
    $relations = SubscriberCustomField::select('custom_field_id')
      ->select('value')
      ->whereIn('custom_field_id', $custom_field_ids)
      ->where('subscriber_id', $this->id())
      ->findMany();
    foreach ($relations as $relation) {
      $this->{'cf_' . $relation->custom_field_id} = $relation->value;
    }

    return $this;
  }

  function withSegments() {
    $this->segments = $this->segments()->findArray();
    return $this;
  }

  function withSubscriptions() {
    $this->subscriptions = SubscriberSegment::where('subscriber_id', $this->id())
      ->findArray();
    return $this;
  }

  function getCustomField($custom_field_id, $default = null) {
    $custom_field = SubscriberCustomField::select('value')
      ->where('custom_field_id', $custom_field_id)
      ->where('subscriber_id', $this->id())
      ->findOne();

    if ($custom_field instanceof SubscriberCustomField) {
      return $custom_field->value;
    } else {
      return $default;
    }
  }

  function saveCustomFields($custom_fields_data = []) {
    // get custom field ids
    $custom_field_ids = array_keys($custom_fields_data);

    // get custom fields
    $custom_fields = CustomField::whereIdIn($custom_field_ids)->findMany();

    foreach ($custom_fields as $custom_field) {
      $value = (isset($custom_fields_data[$custom_field->id])
        ? $custom_fields_data[$custom_field->id]
        : null
      );
      // format value
      $value = $custom_field->formatValue($value);

      $this->setCustomField($custom_field->id, $value);
    }
  }

  function setCustomField($custom_field_id, $value) {
    return SubscriberCustomField::createOrUpdate([
      'subscriber_id' => $this->id(),
      'custom_field_id' => $custom_field_id,
      'value' => $value,
    ]);
  }

  function setUnconfirmedData(array $subscriber_data) {
    $subscriber_data = self::filterOutReservedColumns($subscriber_data);
    $encoded = json_encode($subscriber_data);
    if (is_string($encoded)) {
      $this->unconfirmed_data = $encoded;
    }
  }

  function getUnconfirmedData() {
    if (!empty($this->unconfirmed_data)) {
      $subscriber_data = json_decode($this->unconfirmed_data, true);
      $subscriber_data = self::filterOutReservedColumns((array)$subscriber_data);
      return $subscriber_data;
    }
    return null;
  }

  static function bulkAddToList($orm, $data = []) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if (!$segment instanceof Segment) return false;

    $count = parent::bulkAction($orm,
      function($subscriber_ids) use($segment) {
        SubscriberSegment::subscribeManyToSegments(
          $subscriber_ids, [$segment->id]
        );
      }
    );

    return [
      'count' => $count,
      'segment' => $segment->name,
    ];
  }

  static function bulkMoveToList($orm, $data = []) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if (!$segment instanceof Segment) return false;

    $count = parent::bulkAction($orm,
      function($subscriber_ids) use($segment) {
        SubscriberSegment::deleteManySubscriptions($subscriber_ids);
        SubscriberSegment::subscribeManyToSegments(
          $subscriber_ids, [$segment->id]
        );
      }
    );

    return [
      'count' => $count,
      'segment' => $segment->name,
    ];
  }

  static function bulkRemoveFromList($orm, $data = []) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if (!$segment instanceof Segment) return false;

    $count = $orm->count();

    parent::bulkAction($orm, function($subscriber_ids) use($segment) {
      SubscriberSegment::deleteManySubscriptions(
        $subscriber_ids, [$segment->id]
      );
    });

    return [
      'count' => $count,
      'segment' => $segment->name,
    ];
  }

  static function bulkRemoveFromAllLists($orm, $data = []) {
    $count = $orm->count();

    parent::bulkAction($orm, function($subscriber_ids) {
      SubscriberSegment::deleteManySubscriptions($subscriber_ids);
    });

    return [
      'count' => $count,
    ];
  }

  static function getTotalSubscribers() {
    return self::whereIn('status', [
      self::STATUS_SUBSCRIBED,
      self::STATUS_UNCONFIRMED,
      self::STATUS_INACTIVE,
    ])
    ->whereNull('deleted_at')
    ->count();
  }

  static function getInactiveSubscribersCount() {
    return self::where('status', self::STATUS_INACTIVE)
    ->whereNull('deleted_at')
    ->count();
  }

  static function bulkTrash($orm) {
    $count = parent::bulkAction($orm, function($subscriber_ids) {
      Subscriber::rawExecute(join(' ', [
          'UPDATE `' . Subscriber::$_table . '`',
          'SET `deleted_at` = NOW()',
          'WHERE `id` IN (' .
            rtrim(str_repeat('?,', count($subscriber_ids)), ',')
          . ')',
          'AND `wp_user_id` IS NULL',
          'AND `is_woocommerce_user` = 0',
        ]),
        $subscriber_ids
      );
    });

    return ['count' => $count];
  }

  static function bulkDelete($orm) {
    $count = parent::bulkAction($orm, function($subscriber_ids) {
      // delete all subscriber/segment relationships
      SubscriberSegment::deleteManySubscriptions($subscriber_ids);
      // delete all subscriber/custom field relationships
      SubscriberCustomField::deleteManySubscriberRelations($subscriber_ids);
      // delete subscribers (except WP Users)
      Subscriber::whereIn('id', $subscriber_ids)
        ->whereNull('wp_user_id')
        ->whereEqual('is_woocommerce_user', 0)
        ->deleteMany();
    });

    return ['count' => $count];
  }

  static function subscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_SUBSCRIBED);
  }

  static function unsubscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNSUBSCRIBED);
  }

  static function unconfirmed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNCONFIRMED);
  }

  static function bounced($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_BOUNCED);
  }

  static function inactive($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_INACTIVE);
  }

  static function withoutSegments($orm) {
    return $orm->select(MP_SUBSCRIBERS_TABLE . '.*')
      ->whereRaw(
        MP_SUBSCRIBERS_TABLE . '.id NOT IN (
          SELECT `subscriber_id`
          FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . '
          WHERE `status` = "' . self::STATUS_SUBSCRIBED . '" AND `segment_id` IN (
            SELECT `id` FROM ' . MP_SEGMENTS_TABLE . ' WHERE `deleted_at` IS NULL
          )
        )'
      );
  }

  static function createMultiple($columns, $values) {
    return self::rawExecute(
      'INSERT INTO `' . self::$_table . '` ' .
      '(' . implode(', ', $columns) . ') ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(' . rtrim(str_repeat('?,', count($columns)), ',') . ')' . ', ',
          count($values)
        ),
        ', '
      ),
      Helpers::flattenArray($values)
    );
  }

  static function updateMultiple($columns, $subscribers, $updated_at = false) {
    $ignore_columns_on_update = [
      'wp_user_id',
      'is_woocommerce_user',
      'email',
      'created_at',
      'status',
      'last_subscribed_at',
    ];
    // check if there is anything to update after excluding ignored columns
    if (!array_diff($columns, $ignore_columns_on_update)) return;
    $subscribers = array_map('array_values', $subscribers);
    $email_position = array_search('email', $columns);
    $sql =
      function($type) use (
        $columns,
        $subscribers,
        $email_position,
        $ignore_columns_on_update
      ) {
        return array_filter(
          array_map(function($column_position, $column_name) use (
            $type,
            $subscribers,
            $email_position,
            $ignore_columns_on_update
          ) {
            if (in_array($column_name, $ignore_columns_on_update)) return;
            $query = array_map(
              function($subscriber) use ($type, $column_position, $email_position) {
                return ($type === 'values') ?
                  [
                    $subscriber[$email_position],
                    $subscriber[$column_position],
                  ] :
                  'WHEN email = ? THEN ?';
              }, $subscribers);
            return ($type === 'values') ?
              Helpers::flattenArray($query) :
              $column_name . '= (CASE ' . implode(' ', $query) . ' END)';
          }, array_keys($columns), $columns)
        );
      };
    return self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET ' . implode(', ', $sql('statement')) . ' ' .
      (($updated_at) ? ', updated_at = "' . $updated_at . '" ' : '') .
      ', unconfirmed_data = NULL ' .
      'WHERE email IN ' .
      '(' . rtrim(str_repeat('?,', count($subscribers)), ',') . ')',
      array_merge(
        Helpers::flattenArray($sql('values')),
        array_column($subscribers, $email_position)
      )
    );
  }

  static function findSubscribersInSegments(array $subscribers_ids, array $segments_ids) {
    return self::getSubscribedInSegments($segments_ids)
      ->whereIn('subscribers.id', $subscribers_ids)
      ->select('subscribers.*');
  }

  static function extractSubscribersIds(array $subscribers) {
    return array_filter(
      array_map(function($subscriber) {
        return (!empty($subscriber->id)) ? $subscriber->id : false;
      }, $subscribers)
    );
  }

  static function setRequiredFieldsDefaultValues($data) {
    $settings = new SettingsController();
    $required_field_default_values = [
      'first_name' => '',
      'last_name' => '',
      'unsubscribe_token' => Security::generateUnsubscribeToken(self::class),
      'status' => (!$settings->get('signup_confirmation.enabled')) ? self::STATUS_SUBSCRIBED : self::STATUS_UNCONFIRMED,
    ];
    foreach ($required_field_default_values as $field => $value) {
      if (!isset($data[$field])) {
        $data[$field] = $value;
      }
    }
    return $data;
  }

  static function extractCustomFieldsFromFromObject($data) {
    $custom_fields = [];
    foreach ($data as $key => $value) {
      if (strpos($key, 'cf_') === 0) {
        $custom_fields[(int)substr($key, 3)] = $value;
        unset($data[$key]);
      }
    }
    return [$data, $custom_fields];
  }

  public function getAllSegmentNamesWithStatus() {
    return Segment::tableAlias('segment')
      ->select('name')
      ->select('subscriber_segment.segment_id', 'segment_id')
      ->select('subscriber_segment.status', 'status')
      ->select('subscriber_segment.updated_at', 'updated_at')
      ->join(
        SubscriberSegment::$_table,
        ['subscriber_segment.segment_id', '=', 'segment.id'],
        'subscriber_segment'
      )
      ->where('subscriber_segment.subscriber_id', $this->id)
      ->orderByAsc('name')
      ->findArray();
  }

  /**
   * This method is here only for BC fix of 3rd party plugin integration.
   * @see https://kb.mailpoet.com/article/195-add-subscribers-through-your-own-form-or-plugin
   * @deprecated
   */
  static function subscribe($subscriber_data = [], $segment_ids = []) {
    trigger_error('Calling Subscriber::subscribe() is deprecated and will be removed. Use MailPoet\API\MP\v1\API instead. ', E_USER_DEPRECATED);
    $service = ContainerWrapper::getInstance()->get(\MailPoet\Subscribers\SubscriberActions::class);
    return $service->subscribe($subscriber_data, $segment_ids);
  }

  /**
   * @deprecated
   */
  static function bulkSendConfirmationEmail($orm) {
    trigger_error('Calling Subscriber::bulkSendConfirmationEmail() is deprecated and will be removed. Use MailPoet\API\MP\v1\API instead. ', E_USER_DEPRECATED);
    $service = ContainerWrapper::getInstance()->get(\MailPoet\Subscribers\SubscriberActions::class);
    return $service->bulkSendConfirmationEmail($orm);
  }
}
