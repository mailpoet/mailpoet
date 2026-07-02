<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

class PlaceholderCollectorTest extends \MailPoetUnitTest {
  public function testItReusesTheSamePlaceholderForRepeatedValuesWithinAPart(): void {
    $collector = new PlaceholderCollector('ns');
    $first = $collector->addHtmlText('same value');
    $second = $collector->addHtmlText('same value');

    verify($second)->equals($first);
    verify($collector->getValues()['html'])->equals([$first => 'same value']);
  }

  public function testItKeepsSeparatePlaceholdersForTheSameValueInDifferentParts(): void {
    $collector = new PlaceholderCollector('ns');
    $html = $collector->addHtmlText('shared');
    $text = $collector->addText('shared');

    verify($html)->notEquals($text);
    $values = $collector->getValues();
    verify($values['html'])->equals([$html => 'shared']);
    verify($values['text'])->equals([$text => 'shared']);
  }

  public function testItOnlyAdvancesTheCounterForNewValues(): void {
    $collector = new PlaceholderCollector('ns');
    $a = $collector->addHtmlText('a');
    $collector->addHtmlText('a');
    $b = $collector->addHtmlText('b');

    verify($a)->equals('{{mailpoet_mss_ns_1}}');
    verify($b)->equals('{{mailpoet_mss_ns_2}}');
  }
}
