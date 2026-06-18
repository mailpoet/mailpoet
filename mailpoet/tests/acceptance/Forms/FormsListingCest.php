<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\FormEntity;
use MailPoet\Test\DataFactories\Form;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group gutenberg-latest
 */
class FormsListingCest {
  public function formsListing(\AcceptanceTester $i) {
    $i->wantTo('Open forms listings page');
    $formName = 'Test Form';
    $form = new Form();
    $form->withName($formName);
    $form->create();

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName, 5, '[data-automation-id="forms_listing"]');
    $i->seeNoJSErrors();
    $i->clickItemRowActionByItemName($formName, 'Move to trash');
    $i->waitForText('No forms were found. Why not create a new one?');
    $i->waitForElementVisible('[data-automation-id="add_new_form"]');
  }

  public function formsNativeFiltersAndSorting(\AcceptanceTester $i) {
    $i->wantTo('Filter and sort the forms listing with native DataViews controls');

    $suffix = 'formfilter' . uniqid();
    $enabledName = "enabledform-{$suffix}";
    $disabledName = "disabledform-{$suffix}";

    (new Form())
      ->withName($enabledName)
      ->withStatus(FormEntity::STATUS_ENABLED)
      ->withCreatedAt(Carbon::now()->subDays(2))
      ->create();
    (new Form())
      ->withName($disabledName)
      ->withStatus(FormEntity::STATUS_DISABLED)
      ->withCreatedAt(Carbon::now())
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->searchFor($suffix);
    $i->waitForText($enabledName, 10, '[data-automation-id="forms_listing"]');
    $i->waitForText($disabledName, 10, '[data-automation-id="forms_listing"]');

    $i->wantTo('Apply a native Status filter and keep only Enabled forms');
    // The DataViews "Add filter" control is an icon-only button, so target its
    // accessible name rather than visible text.
    $addFilter = ['xpath' => '//button[@aria-label="Add filter"]'];
    $i->waitForElementClickable($addFilter);
    $i->click($addFilter);
    $i->waitForText('Status');
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Status")]']);
    $i->waitForElement('.dataviews-filters__search-widget-listitem');
    $i->click(['xpath' => '//*[contains(@class, "dataviews-filters__search-widget-listitem")][.//text()[contains(., "Enabled")]]']);
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
    $i->waitForText($enabledName);
    // Wait for the filtered re-fetch to drop the disabled form before asserting.
    $i->waitForJS(<<<JS
      const text = document.querySelector('.mailpoet-forms-dataviews')?.innerText ?? '';
      return text.includes('{$enabledName}') && !text.includes('{$disabledName}');
    JS, 10);
    $i->dontSee($disabledName);

    $i->wantTo('Sort the forms by creation date in ascending order');
    $i->amOnMailpoetPage('Forms');
    $i->searchFor($suffix);
    $i->waitForText($enabledName, 10, '[data-automation-id="forms_listing"]');
    $i->click(['xpath' => '//th//button[contains(., "Created date")]']);
    $i->waitForText('Sort ascending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort ascending")]']);
    // Wait for the ascending re-fetch: oldest form (enabled) before newest (disabled).
    $i->waitForJS(<<<JS
      const text = Array.from(document.querySelectorAll('.mailpoet-forms-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
      const enabledAt = text.indexOf('{$enabledName}');
      const disabledAt = text.indexOf('{$disabledName}');
      return enabledAt !== -1 && disabledAt !== -1 && enabledAt < disabledAt;
    JS, 10);
    $orderedNames = $i->executeJS(<<<JS
      return Array.from(document.querySelectorAll('.mailpoet-forms-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
    JS);
    \PHPUnit\Framework\Assert::assertIsString($orderedNames);
    $enabledPosition = strpos($orderedNames, $enabledName);
    $disabledPosition = strpos($orderedNames, $disabledName);
    \PHPUnit\Framework\Assert::assertNotFalse($enabledPosition);
    \PHPUnit\Framework\Assert::assertNotFalse($disabledPosition);
    \PHPUnit\Framework\Assert::assertLessThan(
      $disabledPosition,
      $enabledPosition,
      'Oldest form should sort before the newest form ascending'
    );
  }
}
