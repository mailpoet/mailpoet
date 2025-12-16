<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationTemplate;

class AutomationTemplateTest extends \MailPoetUnitTest {
  public function testConstructorAndGetters(): void {
    $automationFactory = fn(): Automation => $this->createMock(Automation::class);

    $template = new AutomationTemplate(
      'test-slug',
      'test-category',
      'Test Name',
      'Test Description',
      $automationFactory,
      ['automationSteps' => 1],
      AutomationTemplate::TYPE_DEFAULT,
      'megaphone',
      'wordpress',
      true
    );

    $this->assertEquals('test-slug', $template->getSlug());
    $this->assertEquals('test-category', $template->getCategory());
    $this->assertEquals('Test Name', $template->getName());
    $this->assertEquals('Test Description', $template->getDescription());
    $this->assertEquals(['automationSteps' => 1], $template->getRequiredCapabilities());
    $this->assertEquals(AutomationTemplate::TYPE_DEFAULT, $template->getType());
    $this->assertEquals('megaphone', $template->getIcon());
    $this->assertEquals('wordpress', $template->getIconType());
    $this->assertTrue($template->isRecommended());
  }

  public function testConstructorWithDefaults(): void {
    $automationFactory = fn(): Automation => $this->createMock(Automation::class);

    $template = new AutomationTemplate(
      'test-slug',
      'test-category',
      'Test Name',
      'Test Description',
      $automationFactory
    );

    $this->assertEquals('test-slug', $template->getSlug());
    $this->assertEquals([], $template->getRequiredCapabilities());
    $this->assertEquals(AutomationTemplate::TYPE_DEFAULT, $template->getType());
    $this->assertNull($template->getIcon());
    $this->assertEquals('wordpress', $template->getIconType());
    $this->assertFalse($template->isRecommended());
  }

  public function testToArray(): void {
    $automationFactory = fn(): Automation => $this->createMock(Automation::class);

    $template = new AutomationTemplate(
      'test-slug',
      'test-category',
      'Test Name',
      'Test Description',
      $automationFactory,
      ['automationSteps' => 2],
      AutomationTemplate::TYPE_PREMIUM,
      'store',
      'wordpress',
      false
    );

    $expected = [
      'slug' => 'test-slug',
      'name' => 'Test Name',
      'category' => 'test-category',
      'type' => AutomationTemplate::TYPE_PREMIUM,
      'required_capabilities' => ['automationSteps' => 2],
      'description' => 'Test Description',
      'icon' => 'store',
      'icon_type' => 'wordpress',
      'is_recommended' => false,
    ];

    $this->assertEquals($expected, $template->toArray());
  }

  public function testToArrayWithSvgIcon(): void {
    $automationFactory = fn(): Automation => $this->createMock(Automation::class);
    $iconUrl = 'https://example.com/assets/img/icons/cart.svg';

    $template = new AutomationTemplate(
      'abandoned-cart',
      'abandoned-cart',
      'Abandoned Cart',
      'Test Description',
      $automationFactory,
      [],
      AutomationTemplate::TYPE_DEFAULT,
      $iconUrl,
      'svg',
      true
    );

    $result = $template->toArray();

    $this->assertEquals($iconUrl, $result['icon']);
    $this->assertEquals('svg', $result['icon_type']);
    $this->assertTrue($result['is_recommended']);
  }

  public function testToArrayWithNullIcon(): void {
    $automationFactory = fn(): Automation => $this->createMock(Automation::class);

    $template = new AutomationTemplate(
      'test-slug',
      'test-category',
      'Test Name',
      'Test Description',
      $automationFactory
    );

    $result = $template->toArray();

    $this->assertNull($result['icon']);
    $this->assertEquals('wordpress', $result['icon_type']);
    $this->assertFalse($result['is_recommended']);
  }

  public function testTypeConstants(): void {
    $this->assertEquals('default', AutomationTemplate::TYPE_DEFAULT);
    $this->assertEquals('premium', AutomationTemplate::TYPE_PREMIUM);
    $this->assertEquals('coming-soon', AutomationTemplate::TYPE_COMING_SOON);
  }
}
