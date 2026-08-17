<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

class PlaceholderCollectorTest extends \MailPoetUnitTest {
  public function testItReusesTheSamePlaceholderForRepeatedTokenInTheSameContext(): void {
    $collector = new PlaceholderCollector('ns');
    $first = $collector->addHtmlText('John', '[subscriber:firstname]');
    $second = $collector->addHtmlText('John', '[subscriber:firstname]');

    verify($second)->equals($first);
    verify($collector->getValues()['html'])->equals([$first => 'John']);
  }

  public function testItKeepsSeparatePlaceholdersForDifferentTokensWithTheSameValue(): void {
    $collector = new PlaceholderCollector('ns');
    // Two subscribers with empty first and last name resolve both tags to the
    // same value, but they must not collapse into one placeholder or the
    // rendered template would differ between subscribers.
    $firstName = $collector->addHtmlText('', '[subscriber:firstname]');
    $lastName = $collector->addHtmlText('', '[subscriber:lastname]');

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
    $asText = $collector->addHtmlText('https://example.com/review', $token);
    $asUrl = $collector->addHtmlUrl('https://example.com/review', $token);

    verify($asText)->notEquals($asUrl);
  }

  public function testItKeepsSeparatePlaceholdersForTheSameValueInDifferentParts(): void {
    $collector = new PlaceholderCollector('ns');
    $html = $collector->addHtmlText('shared', '[subscriber:firstname]');
    $text = $collector->addText('shared', '[subscriber:firstname]');

    verify($html)->notEquals($text);
    $values = $collector->getValues();
    verify($values['html'])->equals([$html => 'shared']);
    verify($values['text'])->equals([$text => 'shared']);
  }

  public function testItFallsBackToValueDedupWhenNoTokenIsGiven(): void {
    $collector = new PlaceholderCollector('ns');
    $first = $collector->addHtmlText('same value');
    $second = $collector->addHtmlText('same value');

    verify($second)->equals($first);
    verify($collector->getValues()['html'])->equals([$first => 'same value']);
  }

  public function testItOnlyAdvancesTheCounterForNewKeys(): void {
    $collector = new PlaceholderCollector('ns');
    $a = $collector->addHtmlText('John', '[subscriber:firstname]');
    $collector->addHtmlText('John', '[subscriber:firstname]');
    $b = $collector->addHtmlText('', '[subscriber:lastname]');

    verify($a)->equals('{{mailpoet_mss_ns_1}}');
    verify($b)->equals('{{mailpoet_mss_ns_2}}');
  }
}
