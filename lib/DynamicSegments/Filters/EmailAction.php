<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class EmailAction implements Filter {

  const SEGMENT_TYPE = 'email';

  const ACTION_OPENED = 'opened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_NOT_CLICKED = 'notClicked';

  private static $allowedActions = [
    EmailAction::ACTION_OPENED,
    EmailAction::ACTION_NOT_OPENED,
    EmailAction::ACTION_CLICKED,
    EmailAction::ACTION_NOT_CLICKED,
  ];

  /** @var int */
  private $newsletterId;

  /** @var int */
  private $linkId;

  /** @var string */
  private $action;

  /** @var string|null */
  private $connect;

  /**
   * @param int $newsletterId
   * @param int $linkId
   * @param string $action
   * @param string|null $connect
   */
  public function __construct($action, $newsletterId, $linkId = null, $connect = null) {
    $this->newsletterId = (int)$newsletterId;
    if ($linkId) {
      $this->linkId = (int)$linkId;
    }
    $this->setAction($action);
    $this->connect = $connect;
  }

  private function setAction($action) {
    if (!in_array($action, EmailAction::$allowedActions)) {
      throw new \InvalidArgumentException("Unknown action " . $action);
    }
    $this->action = $action;
  }

  public function toSql(ORM $orm) {
    if (($this->action === EmailAction::ACTION_CLICKED) || ($this->action === EmailAction::ACTION_NOT_CLICKED)) {
      $table = StatisticsClicks::$_table;
    } else {
      $table = StatisticsOpens::$_table;
    }

    if (($this->action === EmailAction::ACTION_NOT_CLICKED) || ($this->action === EmailAction::ACTION_NOT_OPENED)) {
      $orm->rawJoin(
        'INNER JOIN ' . StatisticsNewsletters::$_table,
        'statssent.subscriber_id = ' . Subscriber::$_table . '.id AND statssent.newsletter_id = ' . $this->newsletterId,
        'statssent'
      );
      $orm->rawJoin(
        'LEFT JOIN ' . $table,
        $this->createNotStatsJoin(),
        'stats'
      );
      $orm->whereNull('stats.id');
    } else {
      $orm->rawJoin(
        'INNER JOIN ' . $table,
        'stats.subscriber_id = ' . Subscriber::$_table . '.id AND stats.newsletter_id = ' . $this->newsletterId,
        'stats'
      );
    }
    if (($this->action === EmailAction::ACTION_CLICKED) && ($this->linkId)) {
      $orm->where('stats.link_id', $this->linkId);
    }
    return $orm;
  }

  private function createNotStatsJoin() {
    $clause = 'statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = ' . $this->newsletterId;
    if (($this->action === EmailAction::ACTION_NOT_CLICKED) && ($this->linkId)) {
      $clause .= ' AND stats.link_id = ' . $this->linkId;
    }
    return $clause;
  }

  public function toArray() {
    return [
      'action' => $this->action,
      'newsletter_id' => $this->newsletterId,
      'link_id' => $this->linkId,
      'connect' => $this->connect,
      'segmentType' => EmailAction::SEGMENT_TYPE,
    ];
  }
}
