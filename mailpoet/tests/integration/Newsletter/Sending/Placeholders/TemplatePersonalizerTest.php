<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;

class TemplatePersonalizerTest extends \MailPoetTest {
  private TemplatePersonalizer $templatePersonalizer;
  private Personalizer $personalizer;
  private Personalization_Tags_Registry $registry;

  /** @var string[] */
  private array $registeredTokens = [];

  public function _before() {
    parent::_before();
    $this->templatePersonalizer = $this->diContainer->get(TemplatePersonalizer::class);
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
    $this->registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
  }

  public function _after() {
    // Tests register throwaway tags on the shared registry; drop them so they don't leak into later tests.
    foreach ($this->registeredTokens as $token) {
      $this->registry->unregister($token);
    }
    parent::_after();
  }

  public function testItReplacesTagValuesWithPlaceholdersAndRecordsThemPerPart(): void {
    $this->registerTag('mailpoet/test-name', function (): string {
      return 'Rosta & Co';
    });
    $this->registerTag('mailpoet/test-url', function (): string {
      return 'https://example.com/review-order/abc?email=rosta%40example.com&source=mss';
    });
    $collector = new PlaceholderCollector('test');
    $subjectSource = 'Hi <!--[mailpoet/test-name]-->';
    $htmlSource = '<p><!--[mailpoet/test-name]--></p><a data-link-href="[mailpoet/test-url]">Review</a>';
    $textSource = '<!--[mailpoet/test-name]--> [Review](http://[mailpoet/test-url%5D)';

    $subject = $this->templatePersonalizer->personalize($subjectSource, [], $collector, PlaceholderCollector::PART_SUBJECT);
    $html = $this->templatePersonalizer->personalize($htmlSource, [], $collector, PlaceholderCollector::PART_HTML);
    $text = $this->templatePersonalizer->personalize($textSource, [], $collector, PlaceholderCollector::PART_TEXT);

    $this->assertSame('Hi {{mp_mss_test_1}}', $subject);
    $this->assertStringContainsString('<p>{{mp_mss_test_2}}</p>', $html);
    $this->assertStringContainsString('href="{{mp_mss_test_3}}"', $html);
    $this->assertStringNotContainsString('data-link-href=', $html);
    // Markdown links are not the Personalizer's business; the text body keeps them for the link resolver.
    $this->assertSame('{{mp_mss_test_4}} [Review](http://[mailpoet/test-url%5D)', $text);
    $this->assertSame([
      'subject' => ['{{mp_mss_test_1}}' => 'Rosta & Co'],
      'html' => [
        '{{mp_mss_test_2}}' => 'Rosta & Co',
        '{{mp_mss_test_3}}' => 'https://example.com/review-order/abc?email=rosta%40example.com&#038;source=mss',
      ],
      'text' => ['{{mp_mss_test_4}}' => 'Rosta & Co'],
    ], $collector->getValues());
    $this->assertReconstructsRenderedOutput($subjectSource, $subject, $collector, PlaceholderCollector::PART_SUBJECT);
    $this->assertReconstructsRenderedOutput($htmlSource, $html, $collector, PlaceholderCollector::PART_HTML);
    $this->assertReconstructsRenderedOutput($textSource, $text, $collector, PlaceholderCollector::PART_TEXT);
  }

  public function testItEscapesTextTagsForMarkupOnlyInTheHtmlPart(): void {
    $this->registerTag('mailpoet/test-name', function (): string {
      return 'Tom & <b>Jerry</b>';
    }, Personalization_Tag::VALUE_TYPE_TEXT);
    $collector = new PlaceholderCollector('ns');

    $this->templatePersonalizer->personalize('<!--[mailpoet/test-name]-->', [], $collector, PlaceholderCollector::PART_SUBJECT);
    $this->templatePersonalizer->personalize('<p><!--[mailpoet/test-name]--></p>', [], $collector, PlaceholderCollector::PART_HTML);
    $this->templatePersonalizer->personalize('<!--[mailpoet/test-name]-->', [], $collector, PlaceholderCollector::PART_TEXT);

    $values = $collector->getValues();
    $this->assertSame(['Tom & <b>Jerry</b>'], array_values($values['subject']));
    $this->assertSame(['Tom &amp; &lt;b&gt;Jerry&lt;/b&gt;'], array_values($values['html']));
    $this->assertSame(['Tom & <b>Jerry</b>'], array_values($values['text']));
  }

  public function testItKeepsTemplateIdenticalWhenOneTokenIsUsedAsTextAndLink(): void {
    $this->registerTag('mailpoet/test-link', function (array $context): string {
      return (string)($context['url'] ?? '');
    });
    $source = '<p><!--[mailpoet/test-link]--></p><a data-link-href="[mailpoet/test-link]">Open</a>';

    // Plain URL: esc_url (href) and HTML escaping (visible text) produce the same string.
    $plain = $this->templatePersonalizer->personalize($source, ['url' => 'https://example.com/plain'], new PlaceholderCollector('ns'), PlaceholderCollector::PART_HTML);
    // URL with '&': esc_url encodes it as &#038; while the visible text keeps the raw '&'.
    $collector = new PlaceholderCollector('ns');
    $ampersand = $this->templatePersonalizer->personalize($source, ['url' => 'https://example.com/?a=1&b=2'], $collector, PlaceholderCollector::PART_HTML);

    // The same token used as visible text and as a link href must keep two separate placeholders
    // regardless of whether the two escapings coincide, so the template is identical across subscribers.
    $this->assertSame($plain, $ampersand);
    $this->assertSame(2, substr_count($plain, '{{mp_mss_ns_'));
    $htmlValues = $collector->getValues()['html'];
    $this->assertContains('https://example.com/?a=1&b=2', $htmlValues);
    $this->assertContains('https://example.com/?a=1&#038;b=2', $htmlValues);
  }

  public function testItEscapesATagEmbeddedInALinkUrlAsAUrlComponent(): void {
    $this->registerTag('mailpoet/test-link', function (): string {
      return 'john@example.com';
    });
    $collector = new PlaceholderCollector('ns');
    // The same tag is a component of two different link URLs, so each link must keep its own URL around it.
    $source = '<a href="https://example.com/a?e=[mailpoet/test-link]">A</a><a href="https://example.com/b?e=[mailpoet/test-link]">B</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame(1, count($collector->getValues()['html']));
    $resolvedHtml = strtr($html, $collector->getValues()['html']);
    // A whole-URL escaping would have turned the address into "http://john@example.com".
    $this->assertStringContainsString('href="https://example.com/a?e=john@example.com"', $resolvedHtml);
    $this->assertStringContainsString('href="https://example.com/b?e=john@example.com"', $resolvedHtml);
    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
  }

  public function testItKeepsAWholeHrefAndAComponentUseOfTheSameTagApart(): void {
    $this->registerTag('mailpoet/test-link', function (): string {
      return 'john@example.com';
    });
    $collector = new PlaceholderCollector('ns');
    // esc_url() turns the whole href into "http://john@example.com" while the component must stay as is.
    $source = '<a href="[mailpoet/test-link]">A</a><a href="https://example.com/?e=[mailpoet/test-link]">B</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame(2, count($collector->getValues()['html']));
    $resolvedHtml = strtr($html, $collector->getValues()['html']);
    $this->assertStringContainsString('href="http://john@example.com"', $resolvedHtml);
    $this->assertStringContainsString('href="https://example.com/?e=john@example.com"', $resolvedHtml);
    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
  }

  public function testItEscapesEmbeddedTagValuesLikeTheRenderedPath(): void {
    $this->registerTag('mailpoet/test-link', function (): string {
      return 'a&b c';
    });
    $collector = new PlaceholderCollector('ns');
    $source = '<a href="https://example.com/?q=[mailpoet/test-link]&x=1">Q</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame(['a&#038;b%20c'], array_values($collector->getValues()['html']));
    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
  }

  public function testItEscapesAWholeUrlTagLikeTheRenderedPath(): void {
    // No scheme: esc_url() falls back to http:// for a whole href, which a component escaping would not do.
    $this->registerTag('mailpoet/test-url', function (): string {
      return 'example.com/deal?price=$10';
    });
    $collector = new PlaceholderCollector('ns');
    $source = '<a data-link-href="[mailpoet/test-url]">Deal</a><a href="http://[mailpoet/test-url]">Deal again</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame('http://example.com/deal?price=$10', array_values($collector->getValues()['html'])[0]);
    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
  }

  public function testItNeutralizesAClosingTitleTagInTitleValues(): void {
    $this->registerTag('mailpoet/test-name', function (): string {
      return 'Hi</title><script>alert(1)</script>';
    }, Personalization_Tag::VALUE_TYPE_TEXT);
    $collector = new PlaceholderCollector('ns');
    $source = '<html><head><title><!--[mailpoet/test-name]--></title></head><body></body></html>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertStringContainsString('<title>{{mp_mss_ns_1}}</title>', $html);
    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
    $this->assertStringNotContainsString('</title><script>', strtr($html, $collector->getValues()['html']));
  }

  /**
   * @dataProvider disallowedSchemeValues
   */
  public function testItDropsALinkValueWithADisallowedSchemeAtTheStartOfAnHref(string $value): void {
    $this->registerTag('mailpoet/test-link', function () use ($value): string {
      return $value;
    });
    $collector = new PlaceholderCollector('ns');
    // esc_url() never prepends a scheme to an href that already contains a colon, so the value has to be
    // checked as the start of the URL on its own. (Without the "/" esc_url() would read the placeholder
    // itself as a scheme and leave the link untouched in both paths.)
    $source = '<a href="[mailpoet/test-link]/?t=12:00">Go</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame([''], array_values($collector->getValues()['html']));
    $this->assertStringNotContainsString('ascript', strtr($html, $collector->getValues()['html']));
  }

  public function disallowedSchemeValues(): array {
    return [
      'plain' => ['javascript:alert(1)'],
      'entity obfuscated' => ['jav&#x09;ascript:alert(1)'],
    ];
  }

  public function testItKeepsAColonInAValueEmbeddedInAnHref(): void {
    $this->registerTag('mailpoet/test-link', function (): string {
      return 'Note: hi';
    });
    $collector = new PlaceholderCollector('ns');
    $source = '<a href="https://example.com/?ref=[mailpoet/test-link]&t=12:00">Go</a>';

    $html = $this->templatePersonalizer->personalize($source, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertReconstructsRenderedOutput($source, $html, $collector, PlaceholderCollector::PART_HTML);
    $this->assertStringContainsString('?ref=Note:%20hi', strtr($html, $collector->getValues()['html']));
  }

  public function testItKeepsPlaceholdersApartBeyondTenLinks(): void {
    $anchors = '';
    for ($i = 1; $i <= 11; $i++) {
      $this->registerTag("mailpoet/test-link-{$i}", function () use ($i): string {
        return "https://example.com/{$i}";
      });
      $anchors .= "<a data-link-href=\"[mailpoet/test-link-{$i}]\">{$i}</a>";
    }
    $collector = new PlaceholderCollector('ns');

    $html = $this->templatePersonalizer->personalize($anchors, [], $collector, PlaceholderCollector::PART_HTML);

    $this->assertSame(11, count($collector->getValues()['html']));
    $this->assertStringContainsString('href="{{mp_mss_ns_1}}"', $html);
    $this->assertStringContainsString('href="{{mp_mss_ns_10}}"', $html);
    $this->assertStringContainsString('href="{{mp_mss_ns_11}}"', $html);
    $this->assertStringNotContainsString('{{mp_mss_ns_1}}0', $html);
    $this->assertReconstructsRenderedOutput($anchors, $html, $collector, PlaceholderCollector::PART_HTML);
  }

  public function testItRestoresThePreviousValueInterceptor(): void {
    $previous = function (string $value): string {
      return $value;
    };
    $this->personalizer->set_value_interceptor($previous);
    try {
      $this->templatePersonalizer->personalize('<p>Hello</p>', [], new PlaceholderCollector('ns'), PlaceholderCollector::PART_HTML);
      $this->assertSame($previous, $this->personalizer->set_value_interceptor(null));
    } finally {
      $this->personalizer->set_value_interceptor(null);
    }
  }

  private function registerTag(string $tokenName, callable $callback, string $valueType = Personalization_Tag::VALUE_TYPE_HTML): void {
    $this->registry->register(new Personalization_Tag($tokenName, $tokenName, 'Test', $callback, [], null, [], $valueType));
    $this->registeredTokens[] = "[{$tokenName}]";
  }

  /**
   * Substituting the recorded values back must give exactly what the rendered path sends.
   */
  private function assertReconstructsRenderedOutput(string $source, string $template, PlaceholderCollector $collector, string $part): void {
    $this->personalizer->set_context([]);
    $rendered = $this->personalizer->personalize_content(
      $source,
      $part === PlaceholderCollector::PART_HTML ? Personalizer::RENDERING_CONTEXT_HTML : Personalizer::RENDERING_CONTEXT_TEXT
    );
    $this->assertSame($rendered, strtr($template, $collector->getValues()[$part]));
  }
}
