<?php declare(strict_types = 1);

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Env;
use MailPoet\Config\Updater;

class UpdaterTest extends \MailPoetTest {
  /** @var Updater */
  public $updater;
  /** @var string */
  public $version;
  /** @var string */
  public $slug;
  /** @var string */
  public $pluginName;

  public function _before() {
    parent::_before();
    $this->pluginName = 'some-plugin/some-plugin.php';
    $this->slug = 'some-plugin';
    $this->version = '0.1';

    $this->updater = new Updater(
      $this->pluginName,
      $this->slug,
      $this->version
    );
  }

  public function testItInitializes() {
    $updater = Stub::make(
      $this->updater,
      [
        'checkForUpdate' => Expected::once(),
      ],
      $this
    );
    $updater->init();
    apply_filters('pre_set_site_transient_update_plugins', null);
  }

  public function testItChecksForUpdates() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time();
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => $this->version . 1,
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    verify($result->last_checked)->greaterThanOrEqual($updateTransient->last_checked);
    verify($result->checked[$this->pluginName])->equals($this->version);
    verify($result->response[$this->pluginName]->slug)->equals($this->slug);
    verify($result->response[$this->pluginName]->plugin)->equals($this->pluginName);
    verify(version_compare(
      $this->version,
      $result->response[$this->pluginName]->new_version,
      '<'
    ))->true();
    verify($result->response[$this->pluginName]->package)->notEmpty();
  }

  public function testItSetsNoupdateKeyIfNoUpdateAvailable() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time();
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => $this->version,
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    verify($result->last_checked)->greaterThanOrEqual($updateTransient->last_checked);
    verify($result->checked[$this->pluginName])->equals($this->version);
    verify($result->no_update[$this->pluginName]->slug)->equals($this->slug);
    verify($result->no_update[$this->pluginName]->plugin)->equals($this->pluginName);
    verify(version_compare(
      $this->version,
      $result->no_update[$this->pluginName]->new_version,
      '='
    ))->true();
  }

  public function testItReturnsObjectIfPassedNonObjectWhenCheckingForUpdates() {
    $result = $this->updater->checkForUpdate(null);
    verify($result instanceof \stdClass)->true();
  }

  private function createUpdater(string $freeVersion, string $latestVersion, string $latestPackage = 'https://example.com/latest.zip', string $installedVersion = '0.1'): Updater {
    $updater = Stub::construct(
      $this->updater,
      [$this->pluginName, $this->slug, $installedVersion],
      [
        'getLatestVersion' => Expected::exactly(1, function () use ($latestVersion, $latestPackage) {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => $latestVersion,
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => $latestPackage,
          ];
        }),
      ],
      $this
    );
    $updater->currentFreeVersion = $freeVersion;
    return $updater;
  }

  private function createTransient(): \stdClass {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time();
    return $updateTransient;
  }

  public function testItOffersUpdateMatchingInstalledFreeWhenLatestIsFarAhead() {
    $result = $this->createUpdater('5.16.0', '9.5.0')->checkForUpdate($this->createTransient());
    $offer = $result->response[$this->pluginName];
    verify($offer->new_version)->equals('5.16.0');
    verify($offer->package)->equals('https://release.mailpoet.com/downloads/mailpoet-premium/5.16.0/mailpoet-premium.zip');
    verify(isset($result->no_update[$this->pluginName]))->false();
    verify(json_encode($result))->stringNotContainsString('9.5.0');
  }

  public function testItOffersUpdateMatchingInstalledFreeWhenLatestIsNextMajor() {
    $result = $this->createUpdater('5.16.0', '6.0.0')->checkForUpdate($this->createTransient());
    $offer = $result->response[$this->pluginName];
    verify($offer->new_version)->equals('5.16.0');
    verify($offer->package)->equals('https://release.mailpoet.com/downloads/mailpoet-premium/5.16.0/mailpoet-premium.zip');
    verify(isset($result->no_update[$this->pluginName]))->false();
    verify(json_encode($result))->stringNotContainsString('6.0.0');
  }

  public function testItOffersUpdateMatchingInstalledFreeWhenFreeUpdateIsPending() {
    $updateTransient = $this->createTransient();
    $updateTransient->response = [];
    $updateTransient->response[Env::$pluginPath] = (object)[
      'id' => 'w.org/plugins/mailpoet',
      'slug' => 'mailpoet',
      'plugin' => 'mailpoet/mailpoet.php',
      'new_version' => '5.17.0',
    ];

    $result = $this->createUpdater('5.16.0', '5.17.0')->checkForUpdate($updateTransient);
    $offer = $result->response[$this->pluginName];
    verify($offer->new_version)->equals('5.16.0');
    verify($offer->package)->equals('https://release.mailpoet.com/downloads/mailpoet-premium/5.16.0/mailpoet-premium.zip');
    verify(isset($result->no_update[$this->pluginName]))->false();
    verify($result->response[Env::$pluginPath]->new_version)->equals('5.17.0');
  }

  public function testItSetsNoUpdateWhenInstalledPremiumMatchesInstalledFree() {
    $updater = $this->createUpdater('5.16.0', '5.17.0', 'https://example.com/latest.zip', '5.16.0');
    $result = $updater->checkForUpdate($this->createTransient());
    verify($result->no_update[$this->pluginName]->new_version)->equals('5.16.0');
    verify(isset($result->response[$this->pluginName]))->false();
  }

  public function testItDoesNotOfferDowngradeWhenInstalledPremiumIsHotfixOfInstalledFreeMinor() {
    $updater = $this->createUpdater('5.39.0', '5.40.0', 'https://example.com/latest.zip', '5.39.1');
    $result = $updater->checkForUpdate($this->createTransient());
    verify(isset($result->response[$this->pluginName]))->false();
    verify($result->no_update[$this->pluginName]->new_version)->equals('5.39.0');
  }

  public function testItOffersNoPackageWhenLatestHasNoPackage() {
    $result = $this->createUpdater('5.16.0', '5.17.0', '')->checkForUpdate($this->createTransient());
    $offer = $result->response[$this->pluginName];
    verify($offer->new_version)->equals('5.16.0');
    verify($offer->package)->equals('');
  }

  public function testItOffersNothingWhenInstalledFreeVersionIsEmpty() {
    $result = $this->createUpdater('', '5.17.0')->checkForUpdate($this->createTransient());
    verify(isset($result->response[$this->pluginName]))->false();
    verify(isset($result->no_update[$this->pluginName]))->false();
  }

  public function testGetCompatibleVersionReturnsCompatibleLatestUnchanged() {
    $latest = (object)['new_version' => '5.16.1', 'package' => 'https://example.com/latest.zip'];
    $this->updater->currentFreeVersion = '5.16.0';
    verify($this->updater->getCompatibleVersion($latest))->same($latest);
  }

  public function testGetCompatibleVersionRewritesIncompatibleLatestWithoutMutatingIt() {
    $latest = (object)['new_version' => '5.17.0', 'package' => 'https://example.com/latest.zip'];
    $this->updater->currentFreeVersion = '5.16.0';
    $result = $this->updater->getCompatibleVersion($latest);
    verify($result)->notSame($latest);
    verify($result->new_version)->equals('5.16.0');
    verify($result->package)->equals('https://release.mailpoet.com/downloads/mailpoet-premium/5.16.0/mailpoet-premium.zip');
    verify($latest->new_version)->equals('5.17.0');
    verify($latest->package)->equals('https://example.com/latest.zip');
  }

  public function testGetCompatibleVersionUsesMinorVersionZeroOfInstalledFree() {
    $latest = (object)['new_version' => '5.40.0', 'package' => 'https://example.com/latest.zip'];
    $this->updater->currentFreeVersion = '5.39.2';
    $result = $this->updater->getCompatibleVersion($latest);
    verify($result->new_version)->equals('5.39.0');
    verify($result->package)->stringContainsString('/5.39.0/');
  }

  public function testIsVersionCompatibleReturnsTrueForCompatibleVersions() {
    // Test compatible versions (same minor version)
    verify($this->updater->isVersionCompatible('5.17.0', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.1', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.0', '5.17.5'))->true();

    // Test compatible versions (free version higher minor)
    verify($this->updater->isVersionCompatible('5.16.0', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('4.20.0', '5.1.0'))->true();
    // multi-digit major versions
    verify($this->updater->isVersionCompatible('10.2.0', '10.3.1'))->true();
    verify($this->updater->isVersionCompatible('5.4.3', '10.4.3'))->true();
    verify($this->updater->isVersionCompatible('10.4.3', '10.4.3'))->true();
    verify($this->updater->isVersionCompatible('10.4.2', '10.4.3'))->true();
  }

  public function testIsVersionCompatibleReturnsFalseForIncompatibleVersions() {
    // Test incompatible versions (premium requires higher free version)
    verify($this->updater->isVersionCompatible('5.18.0', '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('6.0.0', '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('5.17.0', '5.16.0'))->false();
    verify($this->updater->isVersionCompatible('11.0.0', '10.9.9'))->false();
  }

  public function testIsVersionCompatibleReturnsFalseForEmptyVersions() {
    verify($this->updater->isVersionCompatible('', '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('5.17.0', ''))->false();
    verify($this->updater->isVersionCompatible('', ''))->false();
    verify($this->updater->isVersionCompatible(null, '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('5.17.0', null))->false();
  }

  public function testIsVersionCompatibleHandlesIrregularVersionFormats() {
    // Test with different version formats
    verify($this->updater->isVersionCompatible('5.17', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.0', '5.17'))->true();
    verify($this->updater->isVersionCompatible('5.17.0-beta', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.0', '5.17.0-alpha'))->true();
  }
}
