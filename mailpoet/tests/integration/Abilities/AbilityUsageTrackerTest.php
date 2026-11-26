<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilityUsageTracker;
use MailPoet\Analytics\Analytics;
use MailPoet\Settings\SettingsController;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class AbilityUsageTrackerTest extends \MailPoetTest {
  private const TRACKS_URL = 'https://public-api.wordpress.com/rest/v1.1/tracks/record';

  /** @var AbilityUsageTracker */
  private $tracker;

  /** @var array<int, array{url: string, args: array}> */
  private $requests = [];

  public function _before(): void {
    parent::_before();
    $this->tracker = $this->diContainer->get(AbilityUsageTracker::class);
    $this->diContainer->get(SettingsController::class)->set('analytics', ['enabled' => '1']);
    AbilitiesTestHelper::enableDraftAbilities();
    add_filter('pre_http_request', [$this, 'captureRequest'], 10, 3);
  }

  public function _after(): void {
    // Events left in the queue go to the capture, not to Tracks.
    $this->tracker->flush();
    remove_filter('pre_http_request', [$this, 'captureRequest'], 10);
    AbilitiesTestHelper::disableDraftAbilities();
    wp_set_current_user(0);
    parent::_after();
  }

  /** @return mixed */
  public function captureRequest($pre, $args, $url) {
    if ($url !== self::TRACKS_URL) {
      return $pre;
    }
    $this->requests[] = ['url' => $url, 'args' => $args];
    return ['headers' => [], 'body' => '', 'response' => ['code' => 202, 'message' => 'Accepted'], 'cookies' => [], 'filename' => null];
  }

  public function testItSendsAnEventForASuccessfulCall(): void {
    $this->loginAsAdmin();
    // An earlier flush must not keep the next one from being scheduled. The harness restores
    // the hooks between tests, but the tracker is a shared DI service.
    $this->getAbility('mailpoet/get-sending-status')->execute();
    $this->tracker->flush();
    remove_action('shutdown', [$this->tracker, 'flush']);
    $this->clearRequests();
    $result = $this->getAbility('mailpoet/get-sending-status')->execute();
    $this->assertIsArray($result);
    // Abilities of other plugins are not recorded.
    do_action('wp_after_execute_ability', 'core/get-site-info', null, []);
    verify(has_action('shutdown', [$this->tracker, 'flush']))->notFalse();
    $this->tracker->flush();

    verify($this->requests)->arrayCount(1);
    $args = $this->requests[0]['args'];
    verify($args['blocking'])->false();
    $publicId = $this->diContainer->get(Analytics::class)->getPublicId();
    verify(json_decode($args['body'], true))->equals([
      'commonProps' => [
        'public_id' => $publicId,
        'ability_name' => 'mailpoet__get-sending-status',
        'outcome' => 'success',
        'error_code' => 'none',
      ],
      'events' => [[
        '_ut' => 'anon',
        '_ui' => $publicId,
        '_en' => 'mailpoet_ability_called',
      ]],
    ]);
  }

  public function testItSendsTheErrorCodeAnAbilityReturns(): void {
    $this->loginAsAdmin();
    $result = $this->getAbility('mailpoet/get-newsletter-draft')->execute(['id' => 999999]);
    $this->assertInstanceOf(\WP_Error::class, $result);
    $this->tracker->flush();

    verify($this->getSentProps())->equals([[
      'ability_name' => 'mailpoet__get-newsletter-draft',
      'outcome' => 'error',
      'error_code' => 'mailpoet_draft_not_found',
    ]]);
  }

  public function testItRecordsAnExceptionAndRethrowsIt(): void {
    $callback = $this->tracker->wrapExecuteCallback('mailpoet/test-ability', function () {
      throw new \RuntimeException('Failed');
    });
    try {
      $callback();
      $this->fail('The exception was not rethrown.');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('Failed');
    }
    $error = $this->tracker->wrapExecuteCallback('mailpoet/test-ability', function () {
      return new \WP_Error('not a code');
    });
    $error();
    $this->tracker->flush();

    verify($this->getSentProps())->equals([
      ['ability_name' => 'mailpoet__test-ability', 'outcome' => 'error', 'error_code' => 'exception'],
      ['ability_name' => 'mailpoet__test-ability', 'outcome' => 'error', 'error_code' => 'other'],
    ]);
  }

  public function testItSendsNothingWithoutAnalyticsConsent(): void {
    $this->diContainer->get(SettingsController::class)->set('analytics', ['enabled' => '']);
    $this->loginAsAdmin();
    $this->getAbility('mailpoet/get-sending-status')->execute();
    $this->getAbility('mailpoet/get-newsletter-draft')->execute(['id' => 999999]);
    $this->tracker->flush();

    verify($this->requests)->arrayCount(0);
  }

  public function testItSendsAtMost25EventsPerRequest(): void {
    $callback = $this->tracker->wrapExecuteCallback('mailpoet/test-ability', function () {
      return new \WP_Error('mailpoet_test_error');
    });
    for ($i = 0; $i < 26; $i++) {
      $callback();
    }
    $this->tracker->flush();

    verify($this->requests)->arrayCount(25);
  }

  private function clearRequests(): void {
    $this->requests = [];
  }

  private function getAbility(string $name): \WP_Ability {
    $ability = wp_get_ability($name);
    $this->assertInstanceOf(\WP_Ability::class, $ability, $name);
    return $ability;
  }

  private function loginAsAdmin(): void {
    $userId = $this->tester->createWordPressUser('ability-tracking-admin@example.com', 'administrator');
    wp_set_current_user($userId);
  }

  private function getSentProps(): array {
    return array_map(function (array $request): array {
      $body = json_decode($request['args']['body'], true);
      $this->assertIsArray($body);
      $this->assertIsArray($body['commonProps']);
      $props = $body['commonProps'];
      unset($props['public_id']);
      return $props;
    }, $this->requests);
  }
}
