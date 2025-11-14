<?php declare(strict_types = 1);

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
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
        'shouldShowUpdateNotice' => true,
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    verify($result->last_checked)->greaterThanOrEqual($updateTransient->last_checked); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
        'shouldShowUpdateNotice' => true,
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    verify($result->last_checked)->greaterThanOrEqual($updateTransient->last_checked); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($result->checked[$this->pluginName])->equals($this->version);
    verify($result->no_update[$this->pluginName]->slug)->equals($this->slug); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($result->no_update[$this->pluginName]->plugin)->equals($this->pluginName); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(version_compare(
      $this->version,
      $result->no_update[$this->pluginName]->new_version, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      '='
    ))->true();
  }

  public function testItReturnsObjectIfPassedNonObjectWhenCheckingForUpdates() {
    $result = $this->updater->checkForUpdate(null);
    verify($result instanceof \stdClass)->true();
  }

  public function testItSkipsUpdateNoticeWhenCurrentFreeVersionIsIncompatible() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => Expected::exactly(1, function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => '9.5.0', // a very far future version
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        }),
      ],
      $this
    );
    $updater->currentFreeVersion = '5.16.0';

    $result = $updater->checkForUpdate($updateTransient);
    verify(isset($result->response[$this->pluginName]))->false();
    verify(isset($result->no_update[$this->pluginName]))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItSkipsUpdateNoticeWhenVersionsAreIncompatible() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

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
            'new_version' => '6.0.0', // Incompatible version
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $updater->currentFreeVersion = '5.16.0';

    $result = $updater->checkForUpdate($updateTransient);

    // Should return the original transient without modifications when incompatible
    verify(isset($result->response[$this->pluginName]))->false();
    verify(isset($result->no_update[$this->pluginName]))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItChecksForUpdatesWithFreeVersionInTransient() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // Mock free plugin update in transient
    $updateTransient->response = [];
    $updateTransient->response[Env::$pluginPath] = (object)[
      'id' => 'w.org/plugins/mailpoet',
      'slug' => 'mailpoet',
      'plugin' => 'mailpoet/mailpoet.php',
      'new_version' => '5.17.0',
    ];

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
            'new_version' => '5.17.0',
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $updater->currentFreeVersion = '5.16.0';
    $result = $updater->checkForUpdate($updateTransient);

    // Should process the update since versions are compatible
    verify($result->response[$this->pluginName]->new_version)->equals('5.17.0'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testIsVersionCompatibleReturnsTrueForCompatibleVersions() {
    // Test compatible versions (same minor version)
    verify($this->updater->isVersionCompatible('5.17.0', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.1', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('5.17.0', '5.17.5'))->true();

    // Test compatible versions (free version higher minor)
    verify($this->updater->isVersionCompatible('5.16.0', '5.17.0'))->true();
    verify($this->updater->isVersionCompatible('4.20.0', '5.1.0'))->true();
  }

  public function testIsVersionCompatibleReturnsFalseForIncompatibleVersions() {
    // Test incompatible versions (premium requires higher free version)
    verify($this->updater->isVersionCompatible('5.18.0', '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('6.0.0', '5.17.0'))->false();
    verify($this->updater->isVersionCompatible('5.17.0', '5.16.0'))->false();
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

  public function testShouldShowUpdateNoticeReturnsTrueWhenFreeVersionInTransientIsCompatible() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'isVersionCompatible' => Expected::exactly(1, true), // Should only call once and return true
      ],
      $this
    );

    $result = $updater->shouldShowUpdateNotice('5.17.0', '5.17.0');
    verify($result)->true();
  }

  public function testShouldShowUpdateNoticeReturnsTrueWhenCurrentFreeVersionIsCompatible() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [

      ],
      $this
    );
    $updater->currentFreeVersion = '5.16.0';

    $result = $updater->shouldShowUpdateNotice('5.17.0', '5.18.0'); // Incompatible latest, but current is compatible
    verify($result)->true();
  }

  public function testShouldShowUpdateNoticeReturnsFalseWhenCurrentFreeVersionIsIncompatible() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
      ],
      $this
    );
    $updater->currentFreeVersion = '5.16.0';

    $result = $updater->shouldShowUpdateNotice('5.17.0', null); // no free version in transient and current free version is incompatible
    verify($result)->false();
  }

  public function testShouldShowUpdateNoticeReturnsFalseWhenNoVersionsAreCompatible() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'isVersionCompatible' => Expected::exactly(2, false), // Both calls should return false
      ],
      $this
    );

    $result = $updater->shouldShowUpdateNotice('6.0.0', '5.18.0'); // Both incompatible
    verify($result)->false();
  }

  public function testShouldShowUpdateNoticeHandlesNullLatestFreeVersion() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'isVersionCompatible' => Expected::once(true), // Should only call once with MAILPOET_VERSION
      ],
      $this
    );

    $result = $updater->shouldShowUpdateNotice('5.17.0', null);
    verify($result)->true();
  }

  public function testShouldShowUpdateNoticeHandlesEmptyLatestFreeVersion() {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'isVersionCompatible' => Expected::once(true), // Should only call once with MAILPOET_VERSION
      ],
      $this
    );

    $result = $updater->shouldShowUpdateNotice('5.17.0', '');
    verify($result)->true();
  }
}
