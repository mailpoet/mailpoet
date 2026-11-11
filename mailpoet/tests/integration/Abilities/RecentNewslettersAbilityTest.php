<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\Abilities\RecentNewslettersAbility;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\StatisticsUnsubscribes;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class RecentNewslettersAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/list-recent-newsletters';
  private const SUBSCRIBER_EMAIL = 'recent-newsletters-reader@example.com';

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before(): void {
    $this->newsletter = (new Newsletter())
      ->withSubject('October update')
      ->withSentStatus()
      ->withSendingQueue(['count_processed' => 4, 'count_total' => 4])
      ->create();
    $this->newsletter->setSentAt(new Carbon('2026-10-01 09:00:00'));
    $this->entityManager->flush();

    $subscriber = (new Subscriber())->withEmail(self::SUBSCRIBER_EMAIL)->withFirstName('Reader')->create();
    (new StatisticsOpens($this->newsletter, $subscriber))->create();
    (new StatisticsClicks((new NewsletterLink($this->newsletter))->create(), $subscriber))->create();
    $unsubscribe = (new StatisticsUnsubscribes($this->newsletter, $subscriber))->create();
    $unsubscribe->setReason(StatisticsUnsubscribeEntity::REASON_SPAM);
    $this->entityManager->flush();

    AbilitiesTestHelper::register($this->diContainer->get(AbilitiesRegistrar::class));
    wp_set_current_user((int)$this->tester->createWordPressUser('recent-newsletters-editor@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    parent::_after();
  }

  public function testItListsSentNewslettersWithSummaryStatisticsAndNoSubscriberData(): void {
    $result = $this->getAbility()->execute(['limit' => 5]);

    $this->assertIsArray($result);
    $this->assertIsArray($result['newsletters']);
    verify($result['newsletters'])->arrayCount(1);
    $newsletter = $result['newsletters'][0];
    $this->assertIsArray($newsletter);
    unset($newsletter['revenue']); // depends on whether WooCommerce is active in the test environment
    verify($newsletter)->equals([
      'id' => $this->newsletter->getId(),
      'subject' => 'October update',
      'sent_at' => '2026-10-01T09:00:00+00:00',
      'stats_url' => admin_url('admin.php?page=mailpoet-newsletters#/stats/' . $this->newsletter->getId()),
      'total_sent' => 4,
      'tracked_sent' => 4,
      'clicks' => 1,
      'click_rate' => 25.0,
      'opens' => 1,
      'open_rate' => 25.0,
      'machine_opens' => 0,
      'unsubscribes' => 1,
      'bounces' => 0,
    ]);
    verify($result['detailed_statistics_available'])->false();
    verify(wp_json_encode($result))->stringNotContainsString(self::SUBSCRIBER_EMAIL);
    verify(wp_json_encode($result))->stringNotContainsString('Reader');
  }

  public function testItAddsUnsubscribeReasonsWithDetailedAnalytics(): void {
    $ability = new RecentNewslettersAbility(
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      $this->diContainer->get(StatisticsUnsubscribesRepository::class),
      $this->make(CapabilitiesManager::class, [
        'getCapability' => new Capability('detailedAnalytics', Capability::TYPE_BOOLEAN, false),
      ]),
      $this->diContainer->get(WPFunctions::class)
    );

    $result = $ability->execute(['limit' => 5]);

    verify($result['detailed_statistics_available'])->true();
    verify($result['newsletters'][0]['unsubscribe_reasons'])->equals([
      StatisticsUnsubscribeEntity::REASON_NO_LONGER_INTERESTED => 0,
      StatisticsUnsubscribeEntity::REASON_DID_NOT_SIGN_UP => 0,
      StatisticsUnsubscribeEntity::REASON_INAPPROPRIATE_CONTENT => 0,
      StatisticsUnsubscribeEntity::REASON_SPAM => 1,
      StatisticsUnsubscribeEntity::REASON_OTHER => 0,
      StatisticsUnsubscribeEntity::REASON_UNSPECIFIED => 0,
    ]);
  }

  public function testItReturnsAnEmptyListWhenNoCampaignWasSent(): void {
    $this->newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->entityManager->flush();

    $result = $this->getAbility()->execute();

    $this->assertIsArray($result);
    verify($result['newsletters'])->equals([]);
  }

  public function testItReportsMachineOpensSeparately(): void {
    $subscriber = (new Subscriber())->create();
    (new StatisticsOpens($this->newsletter, $subscriber))->withMachineUserAgentType()->create();

    $result = $this->getAbility()->execute(['limit' => 5]);

    $this->assertIsArray($result);
    $this->assertIsArray($result['newsletters']);
    verify(array_column($result['newsletters'], 'machine_opens'))->equals([1]);
  }

  public function testItRejectsLimitsAboveTwenty(): void {
    $result = $this->getAbility()->execute(['limit' => 50]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_input');
  }

  public function testItDeniesUsersWhoCannotManageEmails(): void {
    wp_set_current_user((int)$this->tester->createWordPressUser('recent-newsletters-subscriber@example.com', 'subscriber'));

    $result = $this->getAbility()->execute(['limit' => 5]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_permissions');
  }

  private function getAbility(): \WP_Ability {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability;
  }
}
