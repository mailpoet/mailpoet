<?php declare(strict_types = 1);

namespace MailPoet\Models;

class Model {
  private static array $methodsReturningSelf = [
    'create',
    'forTable',
    'useIdColumn',
    'forceAllDirty',
    'select_expr',
    'rawQuery',
    'tableAlias',
    'select',
    'selectExpr',
    'selectMany',
    'selectManyExpr',
    'rawJoin',
    'innerJoin',
    'join',
    'leftOuterJoin',
    'rightOuterJoin',
    'fullOuterJoin',
    'where',
    'whereEqual',
    'whereNotEqual',
    'whereNotEqual',
    'whereIdIs',
    'whereAnyIs',
    'whereIdIn',
    'whereLike',
    'whereNotLike',
    'whereGt',
    'whereGte',
    'whereLt',
    'whereLte',
    'whereIn',
    'whereNotIn',
    'whereNull',
    'whereNotNull',
    'whereRaw',
    'deleteMany',
    'orderByDesc',
    'orderByAsc',
    'orderByExpr',
    'groupBy',
    'groupByExpr',
    'havingEqual',
    'havingNotEqual',
    'havingIdIs',
    'havingLike',
    'havingNotLike',
    'havingGt',
    'havingGte',
    'havingLt',
    'havingLte',
    'havingIn',
    'havingNotIn',
    'havingNull',
    'havingNotNull',
    'havingRaw',
    'clearCache',
    'hasMany',
    'hasManyThrough',
    'hasOne',
    'set',
    'createOrUpdate',
    'save',
    'duplicate',
    'subscribers',
    'queue',
    'children',
    'parent',
    'segments',
    'segmentRelations',
    'trash',
    'restore',
    'setStatus',
    'withSegments',
    'withDeletedSegments',
    'withSendingQueue',
    'filterWithOptions',
    'filterStatus',
    'findOneWithOptions',
    'filter',
    'pauseAllByNewsletter',
    'setScheduledAllByNewsletter',
    'findOneScheduledByNewsletterIdAndSubscriberId',
    'task',
    'addSubscribers',
    'newsletters',
    'addSubscriber',
    'getWPSegment',
    'getPublic',
    'getTasks',
    'joinWithTasks',
    'joinWithSubscribers',
    'findTaskByNewsletterId',
    'createMultiple',
    'getAllForSubscriber',
    'search',
    'filters',
    'filterBy',
    'filterWithCustomFields',
    'filterWithCustomFieldsForExport',
    'customFields',
    'withCustomFields',
    'withSegments',
    'withSubscriptions',
    'subscribed',
    'unsubscribed',
    'unconfirmed',
    'bounced',
    'inactive',
    'withoutSegments',
    'updateMultiple',
    'findSubscribersInSegments',
    'subscribe',
    'resubscribeToAllSegments',
  ];

  private static array $methodsReturningArray = [
    'getConfig',
    'getQueryLog',
    'findMany',
    'findArray',
    'asArray',
    'getMeta',
    'getSegmentTypes',
    'bulkTrash',
    'bulkDelete',
    'filterOutReservedColumns',
    'getSubscribedInSegments',
    'extractSubscribersIds',
    'extractCustomFieldsFromFromObject',
    'getAllSegmentNamesWithStatus',

  ];

  private static array $methodsReturningFalse = [
    'findOne', // Returns false if not found
    'getByHash', // Returns findOne()
    'resume',
    'complete',
    'getWooCommerceSegment',
    'isWPUser',
    'isWooCommerceUser',
    'getCurrentWPUser',
    'getWooCommerceSegment',
    'getWooCommerceSegmentSubscriber',
    'unsubscribeFromSegments',
    'subscribeToSegments',
    'resetSubscriptions',
    'subscribeManyToSegments',

  ];

  public function __construct() {
    self::deprecationError(get_called_class() . "::" . debug_backtrace()[0]['function']);
  }

  public function __get($name) {
    self::deprecationError(get_called_class() . "::" . debug_backtrace()[0]['function']);
    return null;
  }

  public function __set($name, $value) {
    self::deprecationError(get_called_class() . "::" . debug_backtrace()[0]['function']);
  }

  public static function __callStatic($name, $arguments) {
    self::deprecationError(get_called_class() . "::" . $name);
    return self::getReturnValue($name);
  }

  public function __call($name, $arguments) {
    self::deprecationError(get_called_class() . "::" . $name);
    return self::getReturnValue($name);
  }

  private static function getReturnValue($method) {
    if (in_array($method, self::$methodsReturningSelf, true)) {
      $class = get_called_class();
      return new $class();
    }
    if (in_array($method, self::$methodsReturningArray, true)) {
      return [];
    }
    if (in_array($method, self::$methodsReturningFalse, true)) {
      return false;
    }
    return null;
  }

  private static function deprecationError($methodName) {
    trigger_error(
      'Calling ' . esc_html($methodName) . ' was deprecated and has been removed.',
      E_USER_DEPRECATED
    );
  }
}

class CustomField extends Model {}
class Newsletter extends Model {}
class NewsletterSegment extends Model {}
class ScheduledTask extends Model {}
class ScheduledTaskSubscriber extends Model {}
class Segment extends Model {}
class SendingQueue extends Model {}
class StatisticsNewsletters extends Model {}
class Subscriber extends Model {}
class SubscriberCustomField extends Model {}
class SubscriberSegment extends Model {}
