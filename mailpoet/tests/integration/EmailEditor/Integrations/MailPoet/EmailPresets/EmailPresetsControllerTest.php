<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlock;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Newsletter;

class EmailPresetsControllerTest extends \MailPoetTest {
  private EmailPresetsController $controller;

  private int $userId;

  public function _before() {
    parent::_before();
    $this->diContainer->get(FeatureFlagsRepository::class)->createOrUpdate([
      'name' => FeaturesController::FEATURE_EMAIL_PRESETS,
      'value' => true,
    ]);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    // The plugin booted with the flag off, so register the preset post type now.
    $this->diContainer->get(EmailEditor::class)->initialize();
    Email_Editor_Container::container()->get(Email_Editor::class)->initialize();

    $this->controller = $this->diContainer->get(EmailPresetsController::class);
    $this->userId = (int)$this->tester->createWordPressUser('email-presets-' . uniqid() . '@localhost.test', 'administrator');
    wp_set_current_user($this->userId);
  }

  public function testItCreatesABlankPrivatePresetWithTheDefaultTemplate(): void {
    $preset = $this->getPost($this->controller->create('Weekly digest'));

    verify($preset->post_type)->equals(EmailPresetPostType::NAME); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($preset->post_status)->equals(EmailPresetPostType::STATUS); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($preset->post_title)->equals('Weekly digest'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($preset->post_content)->equals(''); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify((int)$preset->post_author)->equals($this->userId); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(get_post_meta($preset->ID, '_wp_page_template', true))->equals('newsletter');
  }

  public function testItCopiesContentTemplateSubjectAndPreheaderFromABlockEmail(): void {
    $emailId = $this->createBlockEmail('<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->', 'custom-template');
    (new Newsletter())->withSubject('Big sale')->withPreheader('Only today')->withWpPostId($emailId)->create();

    $presetId = $this->controller->create('Copy', $this->getPost($emailId));

    verify($this->getPost($presetId)->post_content)->stringContainsString('<p>Hello</p>'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(get_post_meta($presetId, '_wp_page_template', true))->equals('custom-template');
    verify(get_post_meta($presetId, EmailPresetPostType::META_SUBJECT, true))->equals('Big sale');
    verify(get_post_meta($presetId, EmailPresetPostType::META_PREHEADER, true))->equals('Only today');
  }

  public function testItKeepsPersonalizationTagsInSubjectAndPreheader(): void {
    $emailId = $this->createBlockEmail('');
    (new Newsletter())
      ->withSubject('Hi <!--[user/firstname default="there"]-->, <b>sale</b>')
      ->withPreheader('For <!--[user/email]--> only<script>alert(1)</script>')
      ->withWpPostId($emailId)
      ->create();

    $presetId = $this->controller->create('Copy', $this->getPost($emailId));

    verify(get_post_meta($presetId, EmailPresetPostType::META_SUBJECT, true))->equals('Hi <!--[user/firstname default="there"]-->, sale');
    verify(get_post_meta($presetId, EmailPresetPostType::META_PREHEADER, true))->equals('For <!--[user/email]--> only');
  }

  public function testItKeepsEscapedCharactersWhenCopyingAnEmail(): void {
    if (is_multisite()) {
      // Without unfiltered_html, kses also encodes "&" in block attributes.
      grant_super_admin($this->userId);
      kses_init();
    }
    $content = '<!-- wp:paragraph {"metadata":{"name":"Tom \\u0026 Jerry"}} --><p>Hi</p><!-- /wp:paragraph -->';
    $emailId = $this->createBlockEmail($content);
    (new Newsletter())->withSubject('Tom \\ Jerry')->withWpPostId($emailId)->create();

    $presetId = $this->controller->create('Tom \\ Jerry', $this->getPost($emailId));

    $preset = $this->getPost($presetId);
    verify($preset->post_content)->equals($content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($preset->post_title)->equals('Tom \\ Jerry'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(get_post_meta($presetId, EmailPresetPostType::META_SUBJECT, true))->equals('Tom \\ Jerry');

    $this->controller->rename($preset, 'A \\ B');
    verify($this->getPost($presetId)->post_title)->equals('A \\ B'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItUsesTheDefaultTemplateWhenTheSourceTemplateIsUnknown(): void {
    $emailId = $this->createBlockEmail('', 'default');

    $presetId = $this->controller->create('Copy', $this->getPost($emailId));

    verify(get_post_meta($presetId, '_wp_page_template', true))->equals('newsletter');
  }

  public function testItDuplicatesAPresetWithItsSettings(): void {
    $presetId = $this->controller->create('Original');
    wp_update_post(['ID' => $presetId, 'post_content' => '<!-- wp:paragraph --><p>Body</p><!-- /wp:paragraph -->']);
    update_post_meta($presetId, EmailPresetPostType::META_DESCRIPTION, 'Monday send');
    update_post_meta($presetId, EmailPresetPostType::META_SUBJECT, 'Hello');

    $copy = $this->getPost($this->controller->duplicate($this->getPost($presetId)));

    verify($copy->post_title)->equals('Copy of Original'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($copy->post_content)->stringContainsString('<p>Body</p>'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(get_post_meta($copy->ID, EmailPresetPostType::META_DESCRIPTION, true))->equals('Monday send');
    verify(get_post_meta($copy->ID, EmailPresetPostType::META_SUBJECT, true))->equals('Hello');
  }

  public function testItRenamesAPreset(): void {
    $presetId = $this->controller->create('Old name');

    $this->controller->rename($this->getPost($presetId), 'New name');

    verify($this->getPost($presetId)->post_title)->equals('New name'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItRestoresATrashedPresetAsPrivate(): void {
    $presetId = $this->controller->create('Restore me');

    verify($this->controller->trash($this->getPost($presetId)))->true();
    verify(get_post_status($presetId))->equals('trash');
    verify($this->controller->getPreset($presetId))->null();

    verify($this->controller->restore($this->getPost($presetId)))->true();
    verify(get_post_status($presetId))->equals(EmailPresetPostType::STATUS);
  }

  public function testItDeletesAPresetPermanently(): void {
    $presetId = $this->controller->create('Delete me');

    verify($this->controller->delete($this->getPost($presetId)))->true();

    verify(get_post($presetId))->null();
  }

  public function testItListsPresetsWithSearchSortingAndGroups(): void {
    $alpha = $this->controller->create('Alpha digest');
    $beta = $this->controller->create('Beta promo');
    $trashed = $this->controller->create('Gone');
    $this->controller->trash($this->getPost($trashed));

    $all = $this->controller->list(['orderby' => 'title', 'order' => 'asc']);
    verify(array_column($all['items'], 'ID'))->equals([$alpha, $beta]);
    verify($all['count'])->equals(2);
    verify($all['groups'])->equals(['all' => 2, 'trash' => 1]);

    $search = $this->controller->list(['search' => 'promo']);
    verify(array_column($search['items'], 'ID'))->equals([$beta]);

    $trash = $this->controller->list(['group' => 'trash']);
    verify(array_column($trash['items'], 'ID'))->equals([$trashed]);
  }

  public function testItOnlyAcceptsBlockEmailsAndPresetsAsSources(): void {
    $postId = $this->tester->createPost(['post_type' => 'post', 'post_title' => 'Blog post'])->ID;
    $emailId = $this->createBlockEmail('');
    $presetId = $this->controller->create('Preset');

    verify($this->controller->getSource($postId))->null();
    verify($this->controller->getSource($emailId))->instanceOf(\WP_Post::class);
    verify($this->controller->getSource($presetId))->instanceOf(\WP_Post::class);
  }

  public function testItRendersThePresetForTheCurrentUser(): void {
    $presetId = $this->controller->create('Preview me');
    wp_update_post(['ID' => $presetId, 'post_content' => '<!-- wp:paragraph --><p>Hi <!--[mailpoet/subscriber-email]--></p><!-- /wp:paragraph -->']);
    update_post_meta($presetId, EmailPresetPostType::META_SUBJECT, 'Preview subject');

    $rendered = $this->controller->render($this->getPost($presetId));

    $userEmail = (new \WP_User($this->userId))->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($rendered['subject'])->equals('Preview subject');
    verify($rendered['html'])->stringContainsString('Hi ' . $userEmail);
    verify($rendered['text'])->stringContainsString('Hi ' . $userEmail);
  }

  public function testItRendersTheCouponPlaceholder(): void {
    $presetId = $this->controller->create('Coupon');
    $content = '<!-- wp:woocommerce/coupon-code {"source":"createNew"} --><div class="wp-block-woocommerce-coupon-code"><strong>' . CouponBlock::SAFE_PLACEHOLDER . '</strong></div><!-- /wp:woocommerce/coupon-code -->';
    wp_update_post(['ID' => $presetId, 'post_content' => wp_slash($content)]);

    $rendered = $this->controller->render($this->getPost($presetId));

    verify($rendered['html'])->stringContainsString(CouponBlock::SAFE_PLACEHOLDER);
    verify(get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]))->empty();
  }

  private function getPost(int $postId): \WP_Post {
    $post = get_post($postId);
    $this->assertInstanceOf(\WP_Post::class, $post);
    return $post;
  }

  private function createBlockEmail(string $content, string $templateSlug = ''): int {
    $post = $this->tester->createPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_author' => $this->userId,
      'post_title' => 'Email',
      'post_content' => wp_slash($content),
    ]);
    if ($templateSlug !== '') {
      update_post_meta($post->ID, '_wp_page_template', $templateSlug);
    }
    return $post->ID;
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
  }
}
