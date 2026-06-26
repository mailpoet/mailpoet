<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Date;
use MailPoet\WP\Functions as WPFunctions;

class DateTest extends \MailPoetTest {
  private Date $date;
  private WPFunctions $wp;

  public function _before() {
    parent::_before();
    $this->date = $this->diContainer->get(Date::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItReturnsCurrentDateParts(): void {
    $timestamp = $this->wp->currentTime('timestamp');

    $this->assertSame($this->wp->dateI18n('d', $timestamp), $this->date->getDay([]));
    $this->assertSame($this->wp->dateI18n('jS', $timestamp), $this->date->getDayOrdinal([]));
    $this->assertSame($this->wp->dateI18n('l', $timestamp), $this->date->getDayName([]));
    $this->assertSame($this->wp->dateI18n('m', $timestamp), $this->date->getMonth([]));
    $this->assertSame($this->wp->dateI18n('F', $timestamp), $this->date->getMonthName([]));
    $this->assertSame($this->wp->dateI18n('Y', $timestamp), $this->date->getYear([]));
  }
}
