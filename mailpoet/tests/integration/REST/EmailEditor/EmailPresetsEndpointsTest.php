<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use MailPoet\API\REST\API as RestApi;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPostType;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetsController;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Api;
use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../Test.php';

class EmailPresetsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/email-presets';

  private EmailPresetsController $controller;

  public function _before() {
    parent::_before();
    $this->diContainer->get(FeatureFlagsRepository::class)->createOrUpdate([
      'name' => FeaturesController::FEATURE_EMAIL_PRESETS,
      'value' => true,
    ]);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    // The plugin booted with the flag off, so register the post type and routes now.
    $this->diContainer->get(EmailEditor::class)->initialize();
    Email_Editor_Container::container()->get(Email_Editor::class)->initialize();
    rest_get_server();
    $this->diContainer->get(Api::class)->registerRoutes($this->diContainer->get(RestApi::class));

    $this->controller = $this->diContainer->get(EmailPresetsController::class);
    wp_set_current_user(1);
  }

  public function testItCreatesABlankPreset(): void {
    $response = $this->post(self::BASE_PATH, ['json' => ['title' => 'Weekly digest']]);

    $preset = $response['data'];
    verify($preset['title'])->equals('Weekly digest');
    verify($preset['status'])->equals(EmailPresetPostType::STATUS);
    verify($preset['edit_url'])->stringContainsString('post.php?post=' . $preset['id'] . '&action=edit');
  }

  public function testItSavesABlockEmailAsAPreset(): void {
    $emailId = $this->tester->createPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_title' => 'Email',
      'post_content' => '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->',
    ])->ID;
    (new Newsletter())->withSubject('Big sale')->withWpPostId($emailId)->create();

    $response = $this->post(self::BASE_PATH, ['json' => ['title' => 'From email', 'source_id' => $emailId]]);

    verify($response['data']['subject'])->equals('Big sale');
    $preset = get_post($response['data']['id']);
    $this->assertInstanceOf(\WP_Post::class, $preset);
    verify($preset->post_content)->stringContainsString('<p>Hello</p>'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItRejectsAnUnknownSource(): void {
    $postId = $this->tester->createPost(['post_type' => 'post', 'post_title' => 'Blog post'])->ID;

    $response = $this->post(self::BASE_PATH, ['json' => ['title' => 'Nope', 'source_id' => $postId]]);

    verify($response['code'])->equals('mailpoet_email_preset_source_not_found');
  }

  public function testItRequiresAName(): void {
    $response = $this->post(self::BASE_PATH, ['json' => ['title' => '<b></b>']]);

    verify($response['code'])->equals('mailpoet_email_preset_title_required');
  }

  public function testItListsPresets(): void {
    $this->controller->create('Alpha');
    $this->controller->create('Beta');

    $response = $this->get(self::BASE_PATH, ['query' => ['orderby' => 'title', 'order' => 'asc', 'per_page' => 1]]);

    verify(array_column($response['data']['items'], 'title'))->equals(['Alpha']);
    verify($response['data']['meta'])->equals(['count' => 2, 'pages' => 2]);
    verify($response['data']['groups'])->equals([['name' => 'all', 'count' => 2], ['name' => 'trash', 'count' => 0]]);
  }

  public function testItRenamesAPreset(): void {
    $presetId = $this->controller->create('Old name');

    $response = $this->put(self::BASE_PATH . '/' . $presetId, ['json' => ['title' => 'New name']]);

    verify($response['data']['title'])->equals('New name');
  }

  public function testItDuplicatesAPreset(): void {
    $presetId = $this->controller->create('Original');

    $response = $this->post(self::BASE_PATH . '/' . $presetId . '/duplicate');

    verify($response['data']['title'])->equals('Copy of Original');
    verify($response['data']['id'])->notEquals($presetId);
  }

  public function testItTrashesRestoresAndDeletesAPreset(): void {
    $presetId = $this->controller->create('Lifecycle');

    $trashed = $this->post(self::BASE_PATH . '/' . $presetId . '/trash');
    verify($trashed['data']['status'])->equals('trash');

    $restored = $this->post(self::BASE_PATH . '/' . $presetId . '/restore');
    verify($restored['data']['status'])->equals(EmailPresetPostType::STATUS);

    $this->delete(self::BASE_PATH . '/' . $presetId);
    verify(get_post($presetId))->null();
  }

  public function testItDoesNotRestoreAPresetThatIsNotTrashed(): void {
    $presetId = $this->controller->create('Not trashed');

    $response = $this->post(self::BASE_PATH . '/' . $presetId . '/restore');

    verify($response['code'])->equals('mailpoet_email_preset_not_trashed');
  }

  public function testItRendersAPreview(): void {
    $presetId = $this->controller->create('Preview');
    wp_update_post(['ID' => $presetId, 'post_content' => '<!-- wp:paragraph --><p>Preview body</p><!-- /wp:paragraph -->']);

    $response = $this->get(self::BASE_PATH . '/' . $presetId . '/preview');

    verify($response['data']['subject'])->equals('Preview');
    verify($response['data']['html'])->stringContainsString('Preview body');
  }

  public function testItReturnsNotFoundForOtherPosts(): void {
    $postId = $this->tester->createPost(['post_type' => 'post', 'post_title' => 'Blog post'])->ID;

    $response = $this->put(self::BASE_PATH . '/' . $postId, ['json' => ['title' => 'Hijack']]);

    verify($response['code'])->equals('mailpoet_email_preset_not_found');
    verify(get_the_title($postId))->equals('Blog post');
  }

  public function testItRequiresTheManageEmailsPermission(): void {
    $userId = (int)$this->tester->createWordPressUser('email-presets-author-' . uniqid() . '@localhost.test', 'author');
    wp_set_current_user($userId);

    $response = $this->get(self::BASE_PATH);

    verify($response['code'])->equals('rest_forbidden');
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(FeatureFlagEntity::class);
    $presetIds = get_posts(['post_type' => EmailPresetPostType::NAME, 'post_status' => ['private', 'trash'], 'numberposts' => -1, 'fields' => 'ids']);
    foreach ($presetIds as $presetId) {
      wp_delete_post((int)$presetId, true);
    }
    remove_filter('woocommerce_email_editor_post_types', [$this->diContainer->get(EmailEditor::class), 'addEmailPostType']);
    unregister_post_type(EmailPresetPostType::NAME);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    global $wp_rest_server;
    $wp_rest_server = null;
  }
}
