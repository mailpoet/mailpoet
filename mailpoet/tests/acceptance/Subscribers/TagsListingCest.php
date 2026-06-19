<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;

class TagsListingCest {
  public function tagsNativeFiltersAndSorting(\AcceptanceTester $i) {
    $i->wantTo('Filter and sort the tags listing with native DataViews controls');

    $suffix = 'tagfilter' . uniqid();
    $withSubs = "WithSubs-{$suffix}";
    $empty = "Empty-{$suffix}";

    $tag = (new TagFactory())->withName($withSubs)->create();
    (new TagFactory())->withName($empty)->create();
    (new SubscriberFactory())->withEmail("sub-{$suffix}@example.com")->withTags([$tag])->create();

    $i->login();
    $i->amOnMailpoetPage('tags');
    $i->searchFor($suffix);
    $i->waitForText($withSubs);
    $i->waitForText($empty);

    // Only WithSubs has a subscriber, so the largest count is 1 and the buckets
    // collapse to "None" and the open-ended "1+" bucket.
    $i->wantTo('Apply the native data-driven "Subscribers" bucket filter');
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->waitForText('Subscribers');
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Subscribers")]']);
    $i->waitForElement('.dataviews-filters__search-widget-listitem');
    $i->click(['xpath' => '//*[contains(@class, "dataviews-filters__search-widget-listitem")][.//text()[contains(., "1+")]]']);
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
    $i->waitForText($withSubs);
    $i->dontSee($empty);
    $i->seeInCurrentUrl('subscribers=1');

    $i->wantTo('Honor the subscribers bucket query param on direct navigation');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-tags&search={$suffix}&subscribers=1");
    $i->waitForText($withSubs);
    $i->dontSee($empty);

    $i->wantTo('Honor the "none" subscribers bucket on direct navigation');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-tags&search={$suffix}&subscribers=0");
    $i->waitForText($empty);
    $i->dontSee($withSubs);

    $i->wantTo('Honor the created date query params on direct navigation');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-tags&search={$suffix}&created_to=2000-01-01");
    $i->waitForText('No tags');
    $i->dontSee($withSubs);

    $i->wantTo('Sort the tags by subscribers count descending');
    $i->amOnMailpoetPage('tags');
    $i->searchFor($suffix);
    $i->waitForText($withSubs);
    $i->click(['xpath' => '//th//button[contains(., "Subscribers")]']);
    $i->waitForText('Sort descending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort descending")]']);
    $i->waitForText($withSubs);
    $i->waitForJS(<<<JS
      const text = Array.from(document.querySelectorAll('.mailpoet-tags-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
      const withSubsAt = text.indexOf('{$withSubs}');
      const emptyAt = text.indexOf('{$empty}');
      return withSubsAt !== -1 && emptyAt !== -1 && withSubsAt < emptyAt;
    JS, 10);
  }
}
