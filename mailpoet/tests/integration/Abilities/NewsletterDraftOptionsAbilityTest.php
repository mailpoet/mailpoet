<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class NewsletterDraftOptionsAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/get-newsletter-draft-options';

  public function _before(): void {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_MCP_WRITE_ABILITIES);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    AbilitiesTestHelper::register($this->diContainer->get(AbilitiesRegistrar::class));
    wp_set_current_user((int)$this->tester->createWordPressUser('options-editor@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    $this->diContainer->get(FeaturesController::class)->resetCache();
    wp_clean_theme_json_cache();
    wp_set_current_user(0);
    $this->tester->deleteCreatedUsers();
    parent::_after();
  }

  public function testItListsSegmentsWithSubscribedCountsAndNoSubscriberData(): void {
    $list = (new Segment())->withName('Weekly readers')->create();
    (new Subscriber())->withEmail('options-reader@example.com')->withSegments([$list])->create();

    $result = $this->execute();

    $lists = array_column($result['lists'], null, 'name');
    verify($lists['Weekly readers'])->equals([
      'id' => (int)$list->getId(),
      'name' => 'Weekly readers',
      'type' => 'default',
      'subscribers' => 1,
    ]);
    verify((string)wp_json_encode($result))->stringNotContainsString('options-reader@example.com');
  }

  public function testItReturnsOnlyTheHexColorsOfTheSitePalette(): void {
    add_filter('wp_theme_json_data_theme', function ($data) {
      return $data->update_with([
        'version' => 3,
        'settings' => ['color' => ['palette' => [
          ['name' => 'Brand', 'slug' => 'brand', 'color' => '#0a7d33'],
          ['name' => 'Variable', 'slug' => 'variable', 'color' => 'var(--brand)'],
        ]]],
      ]);
    });
    wp_clean_theme_json_cache();

    $result = $this->execute();

    verify($result['site_colors'])->equals([['name' => 'Brand', 'color' => '#0a7d33']]);
  }

  public function testItListsOnlyStandardBlockEditorDraftsNewestFirst(): void {
    $older = $this->createDraft('Older draft', '2026-01-01 10:00:00');
    $newer = $this->createDraft('Newer draft', '2026-02-01 10:00:00');
    (new Newsletter())->withSubject('Legacy draft')->withDraftStatus()->create();
    $sent = $this->createDraft('Sent email', '2026-03-01 10:00:00');
    $sent->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();
    $this->entityManager->clear();

    $result = $this->execute();

    verify(array_column($result['recent_drafts'], 'id'))->equals([$newer->getId(), $older->getId()]);
    verify($result['recent_drafts'][0]['subject'])->equals('Newer draft');
  }

  public function testItIsRefusedWithoutTheEmailCapability(): void {
    wp_set_current_user((int)$this->tester->createWordPressUser('options-author@example.com', 'author'));

    $ability = wp_get_ability(self::ABILITY);

    $this->assertInstanceOf(\WP_Ability::class, $ability);
    $result = $ability->execute();
    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_permissions');
  }

  private function createDraft(string $subject, string $updatedAt): NewsletterEntity {
    $postId = wp_insert_post(['post_type' => 'mailpoet_email', 'post_status' => 'draft', 'post_title' => $subject]);
    $newsletter = (new Newsletter())->withSubject($subject)->withDraftStatus()->withWpPostId((int)$postId)->create();
    // The timestamp listener overwrites updatedAt on every flush.
    $table = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("UPDATE $table SET updated_at = ? WHERE id = ?", [$updatedAt, $newsletter->getId()]);
    return $newsletter;
  }

  /** @return array{lists: array<int, array<string, mixed>>, site_colors: array<int, array<string, string>>, recent_drafts: array<int, array<string, mixed>>} */
  private function execute(): array {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    $result = $ability->execute();
    $this->assertIsArray($result);
    /** @var array{lists: array<int, array<string, mixed>>, site_colors: array<int, array<string, string>>, recent_drafts: array<int, array<string, mixed>>} $result */
    return $result;
  }
}
