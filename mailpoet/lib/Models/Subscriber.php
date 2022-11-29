<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property int $id
 * @property string $email
 * @property string $firstName
 * @property string $lastName
 * @property string $status
 * @property string|null $subscribedIp
 * @property string|null $confirmedIp
 * @property string|null $confirmedAt
 * @property string|null $lastSubscribedAt
 * @property string|null $deletedAt
 * @property string|null $source
 * @property string|null $linkToken
 * @property int $countConfirmations
 * @property int $wpUserId
 * @property array $segments
 * @property array $subscriptions
 * @property string|null $unconfirmedData
 * @property int $isWoocommerceUser
 */

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';
  const STATUS_BOUNCED = 'bounced';
  const STATUS_INACTIVE = 'inactive';

  const OBSOLETE_LINK_TOKEN_LENGTH = 6;
  const LINK_TOKEN_LENGTH = 32;

  /** @var string|bool */
  public $token;

  public function __construct() {
    parent::__construct();

    $this->addValidations('email', [
      'required' => __('Please enter your email address', 'mailpoet'),
      'validEmail' => __('Your email address is invalid!', 'mailpoet'),
    ]);
  }

  public static function findOne($id = false) {
    if (is_int($id) || (string)(int)$id === $id) {
      return parent::findOne($id);
    } else if (strlen(trim((string)$id)) > 0) {
      return parent::where('email', $id)->findOne();
    }
    return false;
  }

  public function segments() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Segment',
      __NAMESPACE__ . '\SubscriberSegment',
      'subscriber_id',
      'segment_id'
    )
    ->where(MP_SUBSCRIBER_SEGMENT_TABLE . '.status', self::STATUS_SUBSCRIBED);
  }

  public function save() {
    // convert email to lowercase format
    $this->email = strtolower((string)$this->email);
    return parent::save();
  }

  public function delete() {
    // WP Users cannot be deleted
    if (!$this->isWPUser() && !$this->isWooCommerceUser()) {
      // delete all relations to segments
      SubscriberSegment::deleteSubscriptions($this);
      // delete all relations to custom fields
      SubscriberCustomField::deleteSubscriberRelations($this);
      return parent::delete();
    }
    return null;
  }

  public function trash() {
    // WP Users cannot be trashed
    if ($this->isWPUser() || $this->isWooCommerceUser()) {
      return false;
    } else {
      return parent::trash();
    }
  }

  public function isWPUser() {
    return (bool)$this->wpUserId;
  }

  public function isWooCommerceUser() {
    return (bool)$this->isWoocommerceUser;
  }

  /**
   * @deprecated Use the version in \MailPoet\Subscribers\SubscribersRepository::getCurrentWPUser
   */
  public static function getCurrentWPUser() {
    trigger_error('Calling Subscriber::getCurrentWPUser() is deprecated and will be removed. Use MailPoet\Subscribers\SubscribersRepository::getCurrentWPUser(). ', E_USER_DEPRECATED);
    $wpUser = WPFunctions::get()->wpGetCurrentUser();
    if (empty($wpUser->ID)) {
      return false; // Don't look up a subscriber for guests
    }
    return self::where('wp_user_id', $wpUser->ID)->findOne();
  }

  public static function filterOutReservedColumns(array $subscriberData) {
    $reservedColumns = [
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
    $subscriberData = array_diff_key(
      $subscriberData,
      array_flip($reservedColumns)
    );
    return $subscriberData;
  }

  public static function search($orm, $search = '') {
    if (strlen(trim($search)) === 0) {
      return $orm;
    }

    return $orm->where_raw(
      '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
      ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']
    );
  }

  public static function filters($data = []) {
    $group = (!empty($data['group'])) ? $data['group'] : 'all';

    $segments = Segment::orderByAsc('name')
      ->whereNull('deleted_at')
      ->whereIn('type', Segment::getSegmentTypes())
      ->findMany();
    $segmentList = [];
    $segmentList[] = [
      'label' => __('All Lists', 'mailpoet'),
      'value' => '',
    ];

    $subscribersWithoutSegment = self::filter('withoutSegments')
      ->whereNull('deleted_at')
      ->count();
    $subscribersWithoutSegmentLabel = sprintf(
      // translators: %s is the number of subscribers without a segment.
      __('Subscribers without a list (%s)', 'mailpoet'),
      number_format($subscribersWithoutSegment)
    );

    $segmentList[] = [
      'label' => $subscribersWithoutSegmentLabel,
      'value' => 'none',
    ];

    foreach ($segments as $segment) {
      $subscribersCount = 0;
      $subscribers = $segment->subscribers()
        ->filter('groupBy', $group);
      if ($subscribers) {
        $subscribersCount = $subscribers->count();
      }

      $label = sprintf(
        '%s (%s)',
        $segment->name,
        number_format($subscribersCount)
      );

      $segmentList[] = [
        'label' => $label,
        'value' => $segment->id(),
      ];
    }

    $filters = [
      'segment' => $segmentList,
    ];

    return $filters;
  }

  public static function filterBy($orm, $filters = null) {
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

  public static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    } else if ($group === 'all') {
      return $orm->whereNull('deleted_at');
    } else {
      return $orm->filter($group);
    }
  }

  public static function filterWithCustomFields($orm) {
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

  /**
   * @deprecated
   */
  public static function filterWithCustomFieldsForExport($orm) {
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

  public static function getSubscribedInSegments($segmentIds) {
    $subscribers = SubscriberSegment::tableAlias('relation')
      ->whereIn('relation.segment_id', $segmentIds)
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

  /**
   * @param string $customerEmail
   * @return bool|Subscriber
   */
  public static function getWooCommerceSegmentSubscriber($customerEmail) {
    $wcSegment = Segment::getWooCommerceSegment();
    return Subscriber::tableAlias('subscribers')
      ->select('subscribers.*')
      ->where('subscribers.email', $customerEmail)
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'relation.subscriber_id = subscribers.id',
        'relation'
      )
      ->where('relation.segment_id', $wcSegment->id)
      ->where('relation.status', Subscriber::STATUS_SUBSCRIBED)
      ->whereIn('subscribers.status', [Subscriber::STATUS_SUBSCRIBED, Subscriber::STATUS_UNCONFIRMED])
      ->where('subscribers.is_woocommerce_user', 1)
      ->findOne();
  }

  public function customFields() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\CustomField',
      __NAMESPACE__ . '\SubscriberCustomField',
      'subscriber_id',
      'custom_field_id'
    )->select_expr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value');
  }

  public static function createOrUpdate($data = []) {
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
    $segmentIds = false;
    if (array_key_exists('segments', $data)) {
      $segmentIds = (array)$data['segments'];
      unset($data['segments']);
    }

    // if new subscriber, make sure that required fields are set
    if (!$subscriber) {
      $data = self::setRequiredFieldsDefaultValues($data);
    }

    // get custom fields
    list($data, $customFields) = self::extractCustomFieldsFromFromObject($data);

    // wipe any unconfirmed data at this point
    $data['unconfirmed_data'] = null;

    $oldStatus = false;
    $newStatus = false;

    if ($subscriber === false) {
      $subscriber = self::create();
      $subscriber->hydrate($data);
    } else {
      $oldStatus = $subscriber->status;
      $subscriber->set($data);
      $newStatus = $subscriber->status;
    }

    // Update last_subscribed_at when status changes to subscribed
    if ($oldStatus !== self::STATUS_SUBSCRIBED && $subscriber->status === self::STATUS_SUBSCRIBED) {
      $subscriber->set('last_subscribed_at', WPFunctions::get()->currentTime('mysql'));
    }

    if ($subscriber->save()) {
      if (!empty($customFields)) {
        $subscriber->saveCustomFields($customFields);
      }

      // check for status change
      if (
          ($oldStatus === self::STATUS_SUBSCRIBED)
          &&
          ($newStatus === self::STATUS_UNSUBSCRIBED)
      ) {
        // make sure we unsubscribe the user from all segments
        SubscriberSegment::unsubscribeFromSegments($subscriber);
      } else {
        if ($segmentIds !== false) {
          SubscriberSegment::resetSubscriptions($subscriber, $segmentIds);
        }
      }
    }
    return $subscriber;
  }

  public function withCustomFields() {
    $customFields = CustomField::select('id')->findArray();
    if (empty($customFields)) return $this;

    $customFieldIds = array_column($customFields, 'id');
    $relations = SubscriberCustomField::select('custom_field_id')
      ->select('value')
      ->whereIn('custom_field_id', $customFieldIds)
      ->where('subscriber_id', $this->id())
      ->findMany();
    foreach ($relations as $relation) {
      $this->{'cf_' . $relation->customFieldId} = $relation->value;
    }

    return $this;
  }

  public function withSegments() {
    $this->segments = $this->segments()->findArray();
    return $this;
  }

  public function withSubscriptions() {
    $this->subscriptions = SubscriberSegment::where('subscriber_id', $this->id())
      ->findArray();
    return $this;
  }

  public function getCustomField($customFieldId, $default = null) {
    $customField = SubscriberCustomField::select('value')
      ->where('custom_field_id', $customFieldId)
      ->where('subscriber_id', $this->id())
      ->findOne();

    if ($customField instanceof SubscriberCustomField) {
      return $customField->value;
    } else {
      return $default;
    }
  }

  public function saveCustomFields($customFieldsData = []) {
    // get custom field ids
    $customFieldIds = array_keys($customFieldsData);

    // get custom fields
    $customFields = CustomField::whereIdIn($customFieldIds)->findMany();

    foreach ($customFields as $customField) {
      $value = (isset($customFieldsData[$customField->id])
        ? $customFieldsData[$customField->id]
        : null
      );
      // format value
      $value = $customField->formatValue($value);

      $this->setCustomField($customField->id, $value);
    }
  }

  public function setCustomField($customFieldId, $value) {
    return SubscriberCustomField::createOrUpdate([
      'subscriber_id' => $this->id(),
      'custom_field_id' => $customFieldId,
      'value' => $value,
    ]);
  }

  public function setUnconfirmedData(array $subscriberData) {
    $subscriberData = self::filterOutReservedColumns($subscriberData);
    $encoded = json_encode($subscriberData);
    if (is_string($encoded)) {
      $this->unconfirmedData = $encoded;
    }
  }

  public function getUnconfirmedData() {
    if (!empty($this->unconfirmedData)) {
      $subscriberData = json_decode($this->unconfirmedData, true);
      $subscriberData = self::filterOutReservedColumns((array)$subscriberData);
      return $subscriberData;
    }
    return null;
  }

  /**
   * @deprecated Use MailPoet\Util\License\Features\Subscribers::getSubscribersCount or \MailPoet\Subscribers\SubscribersRepository::getTotalSubscribers
   */
  public static function getTotalSubscribers() {
    return self::whereIn('status', [
      self::STATUS_SUBSCRIBED,
      self::STATUS_UNCONFIRMED,
      self::STATUS_INACTIVE,
    ])
    ->whereNull('deleted_at')
    ->count();
  }

  public static function getInactiveSubscribersCount() {
    return self::where('status', self::STATUS_INACTIVE)
    ->whereNull('deleted_at')
    ->count();
  }

  public static function subscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_SUBSCRIBED);
  }

  public static function unsubscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNSUBSCRIBED);
  }

  public static function unconfirmed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNCONFIRMED);
  }

  public static function bounced($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_BOUNCED);
  }

  public static function inactive($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_INACTIVE);
  }

  public static function withoutSegments($orm) {
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

  public static function createMultiple($columns, $values) {
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

  public static function updateMultiple($columns, $subscribers, $updatedAt = false) {
    $ignoreColumnsOnUpdate = [
      'wp_user_id',
      'is_woocommerce_user',
      'email',
      'created_at',
      'last_subscribed_at',
    ];
    // check if there is anything to update after excluding ignored columns
    if (!array_diff($columns, $ignoreColumnsOnUpdate)) return;
    $subscribers = array_map('array_values', $subscribers);
    $emailPosition = array_search('email', $columns);
    $sql =
      function($type) use (
        $columns,
        $subscribers,
        $emailPosition,
        $ignoreColumnsOnUpdate
      ) {
        return array_filter(
          array_map(function($columnPosition, $columnName) use (
            $type,
            $subscribers,
            $emailPosition,
            $ignoreColumnsOnUpdate
          ) {
            if (in_array($columnName, $ignoreColumnsOnUpdate)) return;
            $query = array_map(
              function($subscriber) use ($type, $columnPosition, $emailPosition) {
                return ($type === 'values') ?
                  [
                    $subscriber[$emailPosition],
                    $subscriber[$columnPosition],
                  ] :
                  'WHEN email = ? THEN ?';
              }, $subscribers);
            return ($type === 'values') ?
              Helpers::flattenArray($query) :
              $columnName . '= (CASE ' . implode(' ', $query) . ' END)';
          }, array_keys($columns), $columns)
        );
      };
    return self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET ' . implode(', ', $sql('statement')) . ' ' .
      (($updatedAt) ? ', updated_at = "' . $updatedAt . '" ' : '') .
      ', unconfirmed_data = NULL ' .
      'WHERE email IN ' .
      '(' . rtrim(str_repeat('?,', count($subscribers)), ',') . ')',
      array_merge(
        Helpers::flattenArray($sql('values')),
        array_column($subscribers, $emailPosition)
      )
    );
  }

  public static function findSubscribersInSegments(array $subscribersIds, array $segmentsIds) {
    return self::getSubscribedInSegments($segmentsIds)
      ->whereIn('subscribers.id', $subscribersIds)
      ->select('subscribers.*');
  }

  public static function extractSubscribersIds(array $subscribers) {
    return array_filter(
      array_map(function($subscriber) {
        return (!empty($subscriber->id)) ? $subscriber->id : false;
      }, $subscribers)
    );
  }

  public static function setRequiredFieldsDefaultValues($data) {
    $settings = SettingsController::getInstance();
    $requiredFieldDefaultValues = [
      'first_name' => '',
      'last_name' => '',
      'unsubscribe_token' => Security::generateUnsubscribeToken(self::class),
      'link_token' => Security::generateRandomString(self::LINK_TOKEN_LENGTH),
      'status' => (!$settings->get('signup_confirmation.enabled')) ? self::STATUS_SUBSCRIBED : self::STATUS_UNCONFIRMED,
    ];
    foreach ($requiredFieldDefaultValues as $field => $value) {
      if (!isset($data[$field])) {
        $data[$field] = $value;
      }
    }
    return $data;
  }

  public static function extractCustomFieldsFromFromObject($data) {
    $customFields = [];
    foreach ($data as $key => $value) {
      if (strpos($key, 'cf_') === 0) {
        $customFields[(int)substr($key, 3)] = $value;
        unset($data[$key]);
      }
    }
    return [$data, $customFields];
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
  public static function subscribe($subscriberData = [], $segmentIds = []) {
    trigger_error('Calling Subscriber::subscribe() is deprecated and will be removed. Use MailPoet\API\MP\v1\API instead. ', E_USER_DEPRECATED);
    $service = ContainerWrapper::getInstance()->get(\MailPoet\Subscribers\SubscriberActions::class);
    [$subscriber] = $service->subscribe($subscriberData, $segmentIds);
    return $subscriber;
  }
}
