<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ConfirmTitleAlignmentSettingsInALCBlockCest {
  public function createWelcomeNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Confirm title alignment settings in ALC block');

    // create a post and a newsletter with ALC block
    $subject = 'ALC Newsletter';
    $postTitle = 'Post for ALC newsletter testing';
    $postBody = 'Magna primis leo neque litora quisque phasellus nunc himenaeos per, cursus porttitor rhoncus primis cubilia condimentum magna semper curabitur nibh, nunc nulla porttitor aptent aliquet dui nec accumsan quisque pharetra non pellentesque senectus hendrerit bibendum.';
    $post = $i->cliToArray(['post', 'create', '--format=json', '--porcelain', "--post_title='$postTitle'", "--post_content='$postBody'", '--post_status=publish']);
    $i->cli(['media', 'import', dirname(__DIR__) . '/../_data/600x400.jpg', "--post_id=$post[0]", '--title="A downloaded picture"', '--featured_image']);
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($subject)->create();

    // open the newsletter in editor
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->waitForText($postTitle);

    // open settings
    $i->moveMouseOver('[data-automation-id="alc_posts"]');
    $i->waitForElementVisible('[data-automation-id="settings_tool"]');
    $i->click('[data-automation-id="settings_tool"]');
    $i->waitForElementVisible('[data-automation-id="display_options"]');
    //display only the post we created
    $i->fillField('[data-automation-id="show_max_posts"]', '1');

    $i->click('[data-automation-id="display_options"]');

    // select above excerpt title position
    $i->checkOption('[data-automation-id="title_above_excerpt"]');
    $this->waitAlcToReload($i, $postTitle);

    // assert we have heading and text as a next sibling in a vertical block
    $i->canSeeElement('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');

    // select above post title position
    $i->checkOption('[data-automation-id="title_above_post"]');
    $this->waitAlcToReload($i, $postTitle);

    // assert no vertical element with two text block is present
    $i->cantSeeElementInDOM('.mailpoet_container_vertical > .mailpoet_text_block + .mailpoet_text_block');
  }

  private function waitAlcToReload(\AcceptanceTester $i, $postTitle) {
    $i->wait(1); // wait 1s to give request time to start
    $i->waitForJS("return jQuery.active == 0;", 20);
    $i->waitForText($postTitle);
  }
}
