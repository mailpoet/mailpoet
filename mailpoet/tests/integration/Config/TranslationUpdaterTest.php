<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;

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
    verify($freeTranslation['type'])->equals('plugin');
    verify($freeTranslation['slug'])->equals($this->freeSlug);
    verify($freeTranslation['language'])->equals('fr_FR');
    verify($freeTranslation['version'])->equals($this->freeVersion);
    verify($freeTranslation['updated'])->equals('2021-08-12 14:28:35');
    verify($freeTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-free-0_1-fr_fr.zip');
    $premiumTranslation = $result->translations[1];
    verify($premiumTranslation['type'])->equals('plugin');
    verify($premiumTranslation['slug'])->equals($this->premiumSlug);
    verify($premiumTranslation['language'])->equals('fr_FR');
    verify($premiumTranslation['version'])->equals($this->premiumVersion);
    verify($premiumTranslation['updated'])->equals('2021-08-12 14:28:35');
    verify($premiumTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-premium-0_1-fr_fr.zip');
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
              'fr_FR' => [
                'PO-Revision-Date' => '2021-10-15 05:18:35',
                'Project-Id-Version' => 'MailPoet - MailPoet',
              ],
            ],
            $this->premiumSlug => [
              'fr_FR' => [
                'PO-Revision-Date' => '2021-10-15 05:18:35',
                'Project-Id-Version' => 'MailPoet - MailPoet Premium',
              ],
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

  public function testItDoesNotOverrideNewerVersionInCaseItWasInstalledFromDotOrg(): void {
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
              'fr_FR' => [
                'PO-Revision-Date' => '2021-10-15 05:18:35',
                'Project-Id-Version' => 'MailPoet - Stable (latest release)',
              ],
            ],
            $this->premiumSlug => [
              'fr_FR' => ['PO-Revision-Date' => '2021-10-15 05:18:35'],
              'Project-Id-Version' => 'MailPoet - MailPoet Premium',
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
    expect($result->translations)->notEmpty();
    $freeTranslation = $result->translations[0];
    verify($freeTranslation['type'])->equals('plugin');
    verify($freeTranslation['slug'])->equals($this->freeSlug);
    verify($freeTranslation['language'])->equals('fr_FR');
    verify($freeTranslation['version'])->equals($this->freeVersion);
    // We add 1 second to .org so that .com translation are saved as newer.
    verify($freeTranslation['updated'])->equals('2021-10-15 05:18:36');
    verify($freeTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-free-0_1-fr_fr.zip');
  }

  public function testItDoesNotInstallDotOrgTranslationsInCaseThereIsLanguagePackFromDotCom(): void {
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
          return ['fr_FR', 'cs_CZ'];
        },
        'wpGetInstalledTranslations' => function() {
          return [];
        },
      ],
      $this
    );

    $updateTransient = new \stdClass;
    $updateTransient->translations = [
      // To be removed: Available on translate.wordpress.com
      [
        'type' => 'plugin',
        'slug' => 'mailpoet',
        'language' => 'fr_FR',
      ],
      // To be kept: Not available on translate.wordpress.com, so we want to install at least translations from .org
      [
        'type' => 'plugin',
        'slug' => 'mailpoet',
        'language' => 'cs_CZ',
      ],
      // To be kept: We don't want to touch other plugins
      [
        'type' => 'plugin',
        'slug' => 'askimet',
        'language' => 'fr_FR',
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
    expect($result->translations)->count(4); // askimet + mailpoet cs_CZ and two packs from .com

    $mailPoetCs = $result->translations[0];
    verify($mailPoetCs['slug'])->equals('mailpoet');
    verify($mailPoetCs['language'])->equals('cs_CZ');

    $askimetFr = $result->translations[1];
    verify($askimetFr['slug'])->equals('askimet');
    verify($askimetFr['language'])->equals('fr_FR');

    $mailpoetFr = $result->translations[2];
    verify($mailpoetFr['slug'])->equals('mailpoet');
    verify($mailpoetFr['language'])->equals('fr_FR');
    expect($mailpoetFr['package'])->stringContainsString('translate.files.wordpress.com');

    $mailpoetPremiumFr = $result->translations[3];
    verify($mailpoetPremiumFr['slug'])->equals('mailpoet-premium');
    verify($mailpoetPremiumFr['language'])->equals('fr_FR');
    expect($mailpoetPremiumFr['package'])->stringContainsString('translate.files.wordpress.com');
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

  public function testItDoesNotThrowErrorIfWrongEncodingInLocales(): void {
    $wpFunctions = Stub::construct(
      $this->wp,
      [],
      [
        'wpRemotePost' => function($url, $args) {
          if ($args['body'] === false) {
            return new WP_Error('error', 'error');
          }
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
          return ['fr_FR', "\xB1\x31"];
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
    verify($freeTranslation['type'])->equals('plugin');
    verify($freeTranslation['slug'])->equals($this->freeSlug);
    verify($freeTranslation['language'])->equals('fr_FR');
    verify($freeTranslation['version'])->equals($this->freeVersion);
    verify($freeTranslation['updated'])->equals('2021-08-12 14:28:35');
    verify($freeTranslation['package'])->equals('https:\/\/translate.files.wordpress.com\/2021\/08\/mailpoet-free-0_1-fr_fr.zip');
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
