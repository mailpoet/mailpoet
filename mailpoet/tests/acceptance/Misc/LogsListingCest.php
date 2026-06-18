<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Log as LogFactory;
use MailPoetVendor\Carbon\Carbon;

class LogsListingCest {
  public function logsNativeFiltersAndSorting(\AcceptanceTester $i) {
    $i->wantTo('Filter and sort the logs listing with native DataViews controls');

    $suffix = 'logfilter' . uniqid();
    $twoDaysAgo = Carbon::now()->subDays(2);
    $oneDayAgo = Carbon::now()->subDays(1);
    $today = Carbon::now();

    (new LogFactory())
      ->withName("cron-{$suffix}")
      ->withMessage("error-message-{$suffix}")
      ->withLevel(400)
      ->withCreatedAt($twoDaysAgo)
      ->create();
    (new LogFactory())
      ->withName("mailer-{$suffix}")
      ->withMessage("warning-message-{$suffix}")
      ->withLevel(300)
      ->withCreatedAt($oneDayAgo)
      ->create();
    (new LogFactory())
      ->withName("queue-{$suffix}")
      ->withMessage("debug-message-{$suffix}")
      ->withLevel(100)
      ->withCreatedAt($today)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('logs');
    $i->searchFor($suffix);
    $i->waitForText("error-message-{$suffix}");
    $i->waitForText("warning-message-{$suffix}");
    $i->waitForText("debug-message-{$suffix}");

    $i->wantTo('Toggle a message open and closed with the native row action');
    // The primary action button sits behind the cell wrapper for Selenium's
    // hit-test, so drive it with a JS click by its (state-dependent) label.
    $clickRowAction = function (string $label) use ($i): void {
      $i->executeJS(
        "Array.from(document.querySelectorAll('.mailpoet-logs-dataviews button'))" .
        ".find((b) => b.textContent.trim() === " . json_encode($label) . ")?.click();"
      );
    };
    $clickRowAction('Show more');
    $i->waitForJS(
      "return document.querySelector('.mailpoet-logs-message-full') !== null;",
      10
    );
    $clickRowAction('Show less');
    $i->waitForJS(
      "return document.querySelector('.mailpoet-logs-message-full') === null;",
      10
    );

    $i->wantTo('Apply a native Severity filter and keep only Error logs');
    $i->click('Add filter');
    $i->waitForText('Severity');
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Severity")]']);
    $i->waitForElement('.dataviews-filters__search-widget-listitem');
    $i->click(['xpath' => '//*[contains(@class, "dataviews-filters__search-widget-listitem")][.//text()[contains(., "Error")]]']);
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
    $i->waitForText("error-message-{$suffix}");
    // Wait for the filtered re-fetch to drop the non-matching rows before
    // asserting; error-message is present before and after, so it can't gate.
    $i->waitForJS(<<<JS
      const text = document.querySelector('.mailpoet-logs-dataviews')?.innerText ?? '';
      return !text.includes('warning-message-{$suffix}') &&
        !text.includes('debug-message-{$suffix}');
    JS, 10);
    $i->dontSee("warning-message-{$suffix}");
    $i->dontSee("debug-message-{$suffix}");
    $i->seeInCurrentUrl('log_level=400');

    $i->wantTo('Honor the new log_level query param on direct navigation');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-logs&search={$suffix}&log_level=300");
    $i->waitForText("warning-message-{$suffix}");
    $i->dontSee("error-message-{$suffix}");
    $i->dontSee("debug-message-{$suffix}");

    $i->wantTo('Honor legacy from/to/search query params on direct navigation');
    $i->amOnPage(
      "/wp-admin/admin.php?page=mailpoet-logs&search={$suffix}" .
      '&from=' . $oneDayAgo->format('Y-m-d') .
      '&to=' . $oneDayAgo->format('Y-m-d')
    );
    $i->waitForText("warning-message-{$suffix}");
    $i->dontSee("error-message-{$suffix}");
    $i->dontSee("debug-message-{$suffix}");

    $i->wantTo('Sort the logs by creation date in ascending order');
    $i->amOnMailpoetPage('logs');
    $i->searchFor($suffix);
    $i->waitForText("error-message-{$suffix}");
    $i->click(['xpath' => '//th//button[contains(., "Created On")]']);
    $i->waitForText('Sort ascending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort ascending")]']);
    $i->waitForText("error-message-{$suffix}");
    // Wait for the ascending re-fetch to re-render before snapshotting the order.
    $i->waitForJS(<<<JS
      const text = Array.from(document.querySelectorAll('.mailpoet-logs-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
      const errorAt = text.indexOf('error-message-{$suffix}');
      const debugAt = text.indexOf('debug-message-{$suffix}');
      return errorAt !== -1 && debugAt !== -1 && errorAt < debugAt;
    JS, 10);
    $orderedMessages = $i->executeJS(<<<JS
      return Array.from(document.querySelectorAll('.mailpoet-logs-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
    JS);
    \PHPUnit\Framework\Assert::assertIsString($orderedMessages);
    $errorPosition = strpos($orderedMessages, "error-message-{$suffix}");
    $debugPosition = strpos($orderedMessages, "debug-message-{$suffix}");
    \PHPUnit\Framework\Assert::assertNotFalse($errorPosition);
    \PHPUnit\Framework\Assert::assertNotFalse($debugPosition);
    \PHPUnit\Framework\Assert::assertLessThan(
      $debugPosition,
      $errorPosition,
      'Oldest log (Error) should sort before the newest log (Debug) ascending'
    );
  }
}
