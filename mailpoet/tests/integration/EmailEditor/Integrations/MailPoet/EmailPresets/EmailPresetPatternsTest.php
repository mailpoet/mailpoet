<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;

class EmailPresetPatternsTest extends \MailPoetTest {
  private EmailPresetPatterns $patterns;

  /** @var int[] */
  private array $presetIds = [];

  public function _before() {
    parent::_before();
    $this->patterns = $this->diContainer->get(EmailPresetPatterns::class);
  }

  public function testItRegistersSavedPresetsHiddenFromTheInserter(): void {
    $presetId = $this->createPreset('Launch', '<!-- wp:paragraph --><p>Launch day</p><!-- /wp:paragraph -->');
    update_post_meta($presetId, EmailPresetPostType::META_DESCRIPTION, 'For product launches');

    $this->patterns->registerPatterns();

    $pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered(EmailPresetPatterns::NAME_PREFIX . $presetId);
    $this->assertIsArray($pattern);
    verify($pattern['title'])->equals('Launch');
    verify($pattern['description'])->equals('For product launches');
    verify($pattern['content'])->stringContainsString('Launch day');
    verify($pattern['categories'])->equals([EmailPresetPatterns::CATEGORY]);
    verify($pattern['templateTypes'])->equals(['email-template']);
    verify($pattern['postTypes'])->equals(EmailEditor::EDITOR_POST_TYPES);
    verify($pattern['inserter'])->false();
    verify(\WP_Block_Pattern_Categories_Registry::get_instance()->is_registered(EmailPresetPatterns::CATEGORY))->true();
  }

  public function testItSkipsEmptyAndTrashedPresets(): void {
    $emptyId = $this->createPreset('Empty', '');
    $trashedId = $this->createPreset('Trashed', '<!-- wp:paragraph --><p>Gone</p><!-- /wp:paragraph -->', 'trash');

    $this->patterns->registerPatterns();

    $registry = \WP_Block_Patterns_Registry::get_instance();
    verify($registry->is_registered(EmailPresetPatterns::NAME_PREFIX . $emptyId))->false();
    verify($registry->is_registered(EmailPresetPatterns::NAME_PREFIX . $trashedId))->false();
  }

  public function testItMovesSavedPresetsFirstInThePatternsResponse(): void {
    $response = new \WP_REST_Response([
      ['name' => 'mailpoet/newsletter-content'],
      ['name' => EmailPresetPatterns::NAME_PREFIX . '1'],
    ]);
    $request = new \WP_REST_Request('GET', '/wp/v2/block-patterns/patterns');

    $result = $this->patterns->moveSavedPresetsFirst($response, [], $request);

    $this->assertInstanceOf(\WP_REST_Response::class, $result);
    $data = $result->get_data();
    $this->assertIsArray($data);
    verify(array_column($data, 'name'))->equals([
      EmailPresetPatterns::NAME_PREFIX . '1',
      'mailpoet/newsletter-content',
    ]);
  }

  public function testItLeavesOtherResponsesUnchanged(): void {
    $data = [['name' => 'a'], ['name' => EmailPresetPatterns::NAME_PREFIX . '1']];
    $response = new \WP_REST_Response($data);

    $result = $this->patterns->moveSavedPresetsFirst($response, [], new \WP_REST_Request('GET', '/wp/v2/posts'));

    $this->assertInstanceOf(\WP_REST_Response::class, $result);
    verify($result->get_data())->equals($data);
  }

  private function createPreset(string $title, string $content, string $status = EmailPresetPostType::STATUS): int {
    $presetId = (int)wp_insert_post([
      'post_type' => EmailPresetPostType::NAME,
      'post_status' => $status,
      'post_title' => $title,
      'post_content' => $content,
    ]);
    $this->presetIds[] = $presetId;
    return $presetId;
  }

  public function _after() {
    parent::_after();
    $registry = \WP_Block_Patterns_Registry::get_instance();
    foreach ($this->presetIds as $presetId) {
      $name = EmailPresetPatterns::NAME_PREFIX . $presetId;
      if ($registry->is_registered($name)) {
        $registry->unregister($name);
      }
      wp_delete_post($presetId, true);
    }
    if (\WP_Block_Pattern_Categories_Registry::get_instance()->is_registered(EmailPresetPatterns::CATEGORY)) {
      unregister_block_pattern_category(EmailPresetPatterns::CATEGORY);
    }
  }
}
