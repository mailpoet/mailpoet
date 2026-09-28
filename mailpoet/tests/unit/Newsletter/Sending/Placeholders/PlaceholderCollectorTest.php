<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

class PlaceholderCollectorTest extends \MailPoetUnitTest {
  public function testItReusesTheSamePlaceholderForRepeatedTokenInTheSameContext(): void {
    $collector = new PlaceholderCollector('ns');
    $first = $collector->addHtml('John', '[subscriber:firstname]');
    $second = $collector->addHtml('John', '[subscriber:firstname]');

    verify($second)->equals($first);
    verify($collector->getValues()['html'])->equals([$first => 'John']);
  }

  public function testItKeepsSeparatePlaceholdersForDifferentTokensWithTheSameValue(): void {
    $collector = new PlaceholderCollector('ns');
    // Two subscribers with empty first and last name resolve both tags to the
    // same value, but they must not collapse into one placeholder or the
    // rendered template would differ between subscribers.
    $firstName = $collector->addHtml('', '[subscriber:firstname]');
    $lastName = $collector->addHtml('', '[subscriber:lastname]');

    verify($firstName)->notEquals($lastName);
    verify($collector->getValues()['html'])->equals([
      $firstName => '',
      $lastName => '',
    ]);
  }

  public function testItKeepsSeparatePlaceholdersForTheSameTokenInDifferentEscapingContexts(): void {
    $collector = new PlaceholderCollector('ns');
    $token = '[woocommerce/order-review-url]';
    // The same token rendered once as visible text and once as a link href
    // needs different escaping, so it must get two placeholders even when the
    // raw value is identical.
    $asText = $collector->addHtml('https://example.com/review', $token);
    $asUrl = $collector->addHtmlUrl('https://example.com/review', $token);

    verify($asText)->notEquals($asUrl);
  }

  public function testItKeepsSeparatePlaceholdersForTheSameValueInDifferentParts(): void {
    $collector = new PlaceholderCollector('ns');
    $html = $collector->addHtml('shared', '[subscriber:firstname]');
    $text = $collector->addText('shared', '[subscriber:firstname]');

    verify($html)->notEquals($text);
    $values = $collector->getValues();
    verify($values['html'])->equals([$html => 'shared']);
    verify($values['text'])->equals([$text => 'shared']);
  }

  public function testItKeepsATextValueAndATextLinkTargetOfTheSameTokenApart(): void {
    $collector = new PlaceholderCollector('ns');
    $token = '[woocommerce/order-review-url]';
    $asText = $collector->addText('https://example.com/review', $token);
    $asUrl = $collector->addTextUrl('https://example.com/review?utm=1', $token);

    verify($asText)->notEquals($asUrl);
    verify($collector->getValues()['text'])->equals([
      $asText => 'https://example.com/review',
      $asUrl => 'https://example.com/review?utm=1',
    ]);
  }

  public function testItBuildsPlaceholdersFromItsPrefix(): void {
    $collector = new PlaceholderCollector('ns');
    verify($collector->getPlaceholderPrefix())->equals('mp_mss_ns_');
    verify($collector->makePlaceholder(3))->equals('{{mp_mss_ns_3}}');
    verify($collector->addHtml('x', '[a]'))->equals($collector->makePlaceholder(1));
  }

  public function testItConvertsMarkupToTextOnlyWhereAsked(): void {
    $collector = new PlaceholderCollector('ns');
    $link = '<a href="https://example.com">Shop</a>';
    // Personalization tags already give plain text for the plain-text parts; legacy shortcodes can give markup.
    $asIs = $collector->addSubjectText($link, '[a]');
    $fromHtml = $collector->addSubjectTextFromHtml($link, '[b]');
    $textAsIs = $collector->addText($link, '[a]');
    $textFromHtml = $collector->addTextFromHtml($link, '[b]');
    $reused = $collector->addTextFromHtml('<b>other</b>', '[b]');

    $values = $collector->getValues();
    verify($values['subject'][$asIs])->equals($link);
    verify($values['subject'][$fromHtml])->stringNotContainsString('<a');
    verify($values['subject'][$fromHtml])->stringContainsString('Shop');
    verify($values['text'][$textAsIs])->equals($link);
    verify($values['text'][$textFromHtml])->stringNotContainsString('<a');
    // the same token reuses its placeholder without converting again
    verify($reused)->equals($textFromHtml);
    verify($values['text'][$textFromHtml])->stringContainsString('Shop');
  }

  public function testItOnlyAdvancesTheCounterForNewKeys(): void {
    $collector = new PlaceholderCollector('ns');
    $a = $collector->addHtml('John', '[subscriber:firstname]');
    $collector->addHtml('John', '[subscriber:firstname]');
    $b = $collector->addHtml('', '[subscriber:lastname]');

    verify($a)->equals('{{mp_mss_ns_1}}');
    verify($b)->equals('{{mp_mss_ns_2}}');
  }
}
