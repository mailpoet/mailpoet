<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Date;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\WP\Functions as WPFunctions;

class DateTest extends \MailPoetTest {
  private Date $date;
  private WPFunctions $wp;

  public function _before() {
    parent::_before();
    $this->date = $this->diContainer->get(Date::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItReturnsDatePartsFromSentNewsletter(): void {
    $sentAt = new \DateTimeImmutable('2010-04-20 15:30:45');
    $newsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->create();
    $newsletter->setSentAt($sentAt);
    $this->entityManager->flush();

    $context = ['newsletter_id' => $newsletter->getId()];
    $timestamp = $sentAt->getTimestamp();

    $this->assertSame($this->wp->dateI18n('d', $timestamp), $this->date->getDay($context));
    $this->assertSame($this->wp->dateI18n('jS', $timestamp), $this->date->getDayOrdinal($context));
    $this->assertSame($this->wp->dateI18n('l', $timestamp), $this->date->getDayName($context));
    $this->assertSame($this->wp->dateI18n('m', $timestamp), $this->date->getMonth($context));
    $this->assertSame($this->wp->dateI18n('F', $timestamp), $this->date->getMonthName($context));
    $this->assertSame($this->wp->dateI18n('Y', $timestamp), $this->date->getYear($context));
  }

  public function testItFallsBackToCurrentDateWhenNewsletterIsNotSent(): void {
    $timestamp = $this->wp->currentTime('timestamp');

    $this->assertSame($this->wp->dateI18n('d', $timestamp), $this->date->getDay([]));
    $this->assertSame($this->wp->dateI18n('m', $timestamp), $this->date->getMonth([]));
    $this->assertSame($this->wp->dateI18n('Y', $timestamp), $this->date->getYear([]));
  }
}
