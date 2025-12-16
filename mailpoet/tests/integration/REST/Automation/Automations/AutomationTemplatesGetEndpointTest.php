<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

/**
 * @group woo
 */
class AutomationTemplatesGetEndpointTest extends AutomationTest {

  private const ENDPOINT_PATH = '/mailpoet/v1/automation-templates';

  public function testGetAllTemplates() {
    $result = $this->get(self::ENDPOINT_PATH, []);
    $this->assertCount(21, $result['data']);
    $this->assertEquals('subscriber-welcome-email', $result['data'][0]['slug']);
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->get(self::ENDPOINT_PATH, []);

    $this->assertCount(21, $data['data']);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->get(self::ENDPOINT_PATH, []);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testGetTemplatesByCategory() {
    //@ToDo: Once we have templates in other categories, we should make this test more specific.
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'welcome',
      ],
    ]);
    $this->assertCount(4, $result['data']);

    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'abandoned-cart',
      ],
    ]);
    $this->assertCount(2, $result['data']);

    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'purchase',
      ],
    ]);
    $this->assertCount(6, $result['data']);

    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'review',
      ],
    ]);
    $this->assertCount(3, $result['data']);

    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'subscriptions',
      ],
    ]);
    $this->assertCount(6, $result['data']);
  }

  public function testTemplatesHaveIconProperties(): void {
    $result = $this->get(self::ENDPOINT_PATH, []);

    foreach ($result['data'] as $template) {
      $this->assertArrayHasKey('icon', $template);
      $this->assertArrayHasKey('icon_type', $template);
      $this->assertArrayHasKey('is_recommended', $template);
      $this->assertContains($template['icon_type'], ['wordpress', 'svg']);
      $this->assertIsBool($template['is_recommended']);
    }
  }

  public function testTemplateIcons(): void {
    $result = $this->get(self::ENDPOINT_PATH, []);

    $iconsByTemplate = [
      // Welcome
      'subscriber-welcome-email' => 'megaphone',
      'user-welcome-email' => 'megaphone',
      'subscriber-welcome-series' => 'megaphone',
      'user-welcome-series' => 'megaphone',
      // Purchase - customer relationship
      'first-purchase' => 'people',
      'thank-loyal-customers' => 'people',
      'win-back-customers' => 'people',
      // Purchase - product focused
      'purchased-product' => 'store',
      'purchased-product-with-tag' => 'store',
      'purchased-in-category' => 'store',
      // Review
      'ask-for-review' => 'starFilled',
      'follow-up-positive-review' => 'starFilled',
      'follow-up-negative-review' => 'starFilled',
      // Subscriptions
      'follow-up-after-subscription-purchase' => 'payment',
      'follow-up-after-subscription-renewal' => 'payment',
      'follow-up-after-failed-renewal' => 'payment',
      'follow-up-on-churned-subscription' => 'payment',
      'follow-up-when-trial-ends' => 'payment',
      'win-back-churned-subscribers' => 'payment',
    ];

    foreach ($result['data'] as $template) {
      // Abandoned cart uses SVG URL
      if ($template['category'] === 'abandoned-cart') {
        $this->assertStringEndsWith('/img/icons/cart.svg', $template['icon'], "Template {$template['slug']} should have cart icon URL");
        continue;
      }
      $expectedIcon = $iconsByTemplate[$template['slug']] ?? null;
      $this->assertNotNull($expectedIcon, "Template {$template['slug']} is missing from test mapping");
      $this->assertEquals($expectedIcon, $template['icon'], "Template {$template['slug']} has unexpected icon");
    }
  }

  public function testRecommendedTemplates(): void {
    $result = $this->get(self::ENDPOINT_PATH, []);

    $expectedRecommended = [
      'subscriber-welcome-email',
      'abandoned-cart',
      'first-purchase',
    ];

    $actualRecommended = array_filter(
      $result['data'],
      fn($template) => $template['is_recommended'] === true
    );

    $actualRecommendedSlugs = array_map(
      fn($template) => $template['slug'],
      $actualRecommended
    );

    $this->assertCount(3, $actualRecommended);
    foreach ($expectedRecommended as $slug) {
      $this->assertContains($slug, $actualRecommendedSlugs);
    }
  }

  public function testAbandonedCartTemplatesUseSvgIcon(): void {
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'abandoned-cart',
      ],
    ]);

    foreach ($result['data'] as $template) {
      $this->assertEquals('svg', $template['icon_type'], "Abandoned cart template {$template['slug']} should use SVG icon");
      $this->assertStringEndsWith('/img/icons/cart.svg', $template['icon'], "Abandoned cart template {$template['slug']} should have cart icon URL");
    }
  }
}
