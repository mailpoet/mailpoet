<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\WP\Functions as WPFunctions;

class TranslationUpdaterTest extends \MailPoetTest {

  /** @var string */
  private $freeSlug;

  /** @var string */
  private $freeVersion;

  /** @var string */
  private $premiumSlug;

  /** @var string|null */
  private $premiumVersion;

  /** @var WPFunctions */
  private $wp;

  /** @var TranslationUpdater */
  private $updater;

  public function _before() {
    parent::_before();
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->freeSlug = 'mailpoet';
    $this->freeVersion = '0.1.1';
    $this->premiumSlug = 'mailpoet-premium';
    $this->premiumVersion = '0.1.0';

    $this->updater = new TranslationUpdater(
      $this->wp,
      $this->freeSlug,
      $this->freeVersion,
      $this->premiumSlug,
      $this->premiumVersion
    );
  }

  public function testItInitializes(): void {
    $updater = Stub::construct(
      $this->updater,
      [
        $this->wp,
        $this->freeSlug,
        $this->freeVersion,
        $this->premiumSlug,
        $this->premiumVersion,
      ],
      [
        'checkForTranslations' => Expected::once(),
      ],
      $this
    );
    $updater->init();
    apply_filters('pre_set_site_transient_update_plugins', null);
  }

  public function testItChecksForAllTranslations(): void {
    $wpFunctions = Stub::construct(
      $this->wp,
      [],
      [
        'wpRemotePost' => function() {
          return [
            'response' => [
              'code' => 200,
            ],
            'body' => json_encode([
              'success' => true,
              'data' => $this->getResponseData(),
            ]),
          ];
        },
        'getAvailableLanguages' => function() {
          return ['fr_FR'];
        },
      ],
      $this
    );

    $updateTransient = new \stdClass;
    $updateTransient->translations = [];
    $updater = Stub::construct(
      $this->updater,
      [
        $wpFunctions,
        $this->freeSlug,
        $this->freeVersion,
        $this->premiumSlug,
        $this->premiumVersion,
      ]
    );
    $result = $updater->checkForTranslations($updateTransient);

    expect($result->translations)->notEmpty();
    $freeTranslation = $result->translations[0];
    expect($freeTranslation['type'])->equals('plugin');
    expect($freeTranslation['slug'])->equals($this->freeSlug);
    expect($freeTranslation['language'])->equals('fr_FR');
    expect($freeTranslation['version'])->equals($this->freeVersion);
    expect($freeTranslation['updated'])->equals('2021-08-12 14:28:35');
    expect($freeTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-free-0_1-fr_fr.zip');
    $premiumTranslation = $result->translations[1];
    expect($premiumTranslation['type'])->equals('plugin');
    expect($premiumTranslation['slug'])->equals($this->premiumSlug);
    expect($premiumTranslation['language'])->equals('fr_FR');
    expect($premiumTranslation['version'])->equals($this->premiumVersion);
    expect($premiumTranslation['updated'])->equals('2021-08-12 14:28:35');
    expect($premiumTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-premium-0_1-fr_fr.zip');
  }

  public function testItDoesNotOverrideNewerVersion(): void {
    $wpFunctions = Stub::construct(
      $this->wp,
      [],
      [
        'wpRemotePost' => function() {
          return [
            'response' => [
              'code' => 200,
            ],
            'body' => json_encode([
              'success' => true,
              'data' => $this->getResponseData(),
            ]),
          ];
        },
        'getAvailableLanguages' => function() {
          return ['fr_FR'];
        },
        'wpGetInstalledTranslations' => function() {
          return [
            $this->freeSlug => [
              'fr_FR' => ['PO-Revision-Date' => '2021-10-15 05:18:35'],
            ],
            $this->premiumSlug => [
              'fr_FR' => ['PO-Revision-Date' => '2021-10-15 05:18:35'],
            ],
          ];
        },
      ],
      $this
    );

    $updateTransient = new \stdClass;
    $updateTransient->translations = [];
    $updater = Stub::construct(
      $this->updater,
      [
        $wpFunctions,
        $this->freeSlug,
        $this->freeVersion,
        $this->premiumSlug,
        $this->premiumVersion,
      ]
    );
    $result = $updater->checkForTranslations($updateTransient);
    expect($result->translations)->isEmpty();
  }

  public function testItDoesNotOverrideOtherPluginTranslations(): void {
    $wpFunctions = Stub::construct(
      $this->wp,
      [],
      [
        'wpRemotePost' => function() {
          return [
            'response' => [
              'code' => 200,
            ],
            'body' => json_encode([
              'success' => true,
              'data' => $this->getResponseData(),
            ]),
          ];
        },
        'getAvailableLanguages' => function() {
          return ['fr_FR'];
        },
      ],
      $this
    );

    $updateTransient = new \stdClass;
    $updateTransient->translations = [
      [
        'type' => 'plugin',
        'slug' => 'some-plugin',
        'language' => 'de_DE',
        'version' => '1.2.3',
        'updated' => '2021-08-12 14:28:35',
        'package' => 'https:\/\/translate.files.wordpress.com\/2021\/08\/some-plugin-1_2_3-de_de.zip',
        'autoupdate' => true,
      ],
    ];
    $updater = Stub::construct(
      $this->updater,
      [
        $wpFunctions,
        $this->freeSlug,
        $this->freeVersion,
        $this->premiumSlug,
        $this->premiumVersion,
      ]
    );
    $result = $updater->checkForTranslations($updateTransient);
    expect($result->translations)->count(3);
  }

  public function testItReturnsObjectIfPassedNonObjectWhenCheckingForTranslations(): void {
    $result = $this->updater->checkForTranslations(null);
    expect($result instanceof \stdClass)->true();
  }

  private function getResponseData(): array {
    return [
      $this->freeSlug => [
        [
          'wp_locale' => 'fr_FR',
          'last_modified' => '2021-08-12 14:28:35',
          'package' => 'https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-free-0_1-fr_fr.zip',
          'version' => $this->freeVersion,
        ],
      ],
      $this->premiumSlug => [
        [
          'wp_locale' => 'fr_FR',
          'last_modified' => '2021-08-12 14:28:35',
          'package' => 'https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-premium-0_1-fr_fr.zip',
          'version' => $this->premiumVersion,
        ],
      ],
    ];
  }
}
