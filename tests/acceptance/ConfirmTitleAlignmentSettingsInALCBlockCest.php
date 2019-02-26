<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class ConfirmTitleAlignmentSettingsInALCBlockCest {

  function createWelcomeNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Confirm title alignment settings in ALC block');
    // create a post and a newsletter with ALC block
    $subject = 'ALC Newsletter';
    $post_title = 'Post for ALC newsletter testing';
    $post_body = 'Magna primis leo neque litora quisque phasellus nunc himenaeos per, cursus porttitor rhoncus primis cubilia condimentum magna semper curabitur nibh, nunc nulla porttitor aptent aliquet dui nec accumsan quisque pharetra non pellentesque senectus hendrerit bibendum.';
    $post = $I->cliToArray(sprintf("post create --format=json --porcelain --post_title='%s' --post_content='%s' --post_status='publish' --allow-root", $post_title, $post_body));
    $I->cli(sprintf("media import https://dummyimage.com/600x400/000/fff.jpg --post_id=%s --title='A downloaded picture' --featured_image --allow-root", $post[0]));
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($subject)->create();
    // open the newsletter in editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->waitForText($post_title, 60);
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
    // wait for xhr to finish loading
    $I->waitForJS("return $.active > 0;", 60);
    $I->waitForJS("return $.active == 0;", 60);
    $I->waitForText($post_title, 60);

    // assert we have heading and text as a next sibling in a vertical block
    $I->canSeeElement('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');

    // select above post title position
    $I->checkOption('[data-automation-id="title_above_post"]');
    // wait for xhr to finish loading
    $I->waitForJS("return $.active > 0;", 60);
    $I->waitForJS("return $.active == 0;", 60);
    $I->waitForText($post_title, 60);

    // assert no vertical element with two text block is present
    $I->cantSeeElement('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');
  }

}
