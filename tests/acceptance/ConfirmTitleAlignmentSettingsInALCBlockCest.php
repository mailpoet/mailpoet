<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ConfirmTitleAlignmentSettingsInALCBlockCest {

  public function createWelcomeNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Confirm title alignment settings in ALC block');

    // create a post and a newsletter with ALC block
    $subject = 'ALC Newsletter';
    $post_title = 'Post for ALC newsletter testing';
    $post_body = 'Magna primis leo neque litora quisque phasellus nunc himenaeos per, cursus porttitor rhoncus primis cubilia condimentum magna semper curabitur nibh, nunc nulla porttitor aptent aliquet dui nec accumsan quisque pharetra non pellentesque senectus hendrerit bibendum.';
    $post = $I->cliToArray(['post', 'create', '--format=json', '--porcelain', "--post_title=$post_title", "--post_content=$post_body", '--post_status=publish']);
    $I->cli(['media', 'import', dirname(__DIR__) . '/_data/600x400.jpg', "--post_id=$post[0]", '--title=A downloaded picture', '--featured_image']);
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($subject)->create();

    // open the newsletter in editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->waitForText($post_title);

    // open settings
    $I->moveMouseOver('[data-automation-id="alc_posts"]');
    $I->waitForElementVisible('[data-automation-id="settings_tool"]');
    $I->click('[data-automation-id="settings_tool"]');
    $I->waitForElementVisible('[data-automation-id="display_options"]');
    //display only the post we created
    $I->fillField('[data-automation-id="show_max_posts"]', '1');

    $I->click('[data-automation-id="display_options"]');

    // select above excerpt title position
    $I->checkOption('[data-automation-id="title_above_excerpt"]');
    $this->waitAlcToReload($I, $post_title);

    // assert we have heading and text as a next sibling in a vertical block
    $I->canSeeElement('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');

    // select above post title position
    $I->checkOption('[data-automation-id="title_above_post"]');
    $this->waitAlcToReload($I, $post_title);

    // assert no vertical element with two text block is present
    $I->cantSeeElementInDOM('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');
  }

  private function waitAlcToReload(\AcceptanceTester $I, $post_title) {
    $I->wait(1); // wait 1s to give request time to start
    $I->waitForJS("return jQuery.active == 0;", 20);
    $I->waitForText($post_title);
  }
}
