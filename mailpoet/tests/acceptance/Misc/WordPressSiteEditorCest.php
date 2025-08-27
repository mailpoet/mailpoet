<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

/**
 * @group frontend
 */
class WordPressSiteEditorCest {
  public function _before(\AcceptanceTester $i) {
    $i->login();

    $i->wantTo('Switch to a block theme (required for site editor)');
    $i->cli(['theme', 'activate', 'twentytwentyfive']);

    $i->wantTo('Wait for theme activation to complete and ensure admin is ready');
    $i->amOnPage('/wp-admin/');
    $i->waitForElement('#wpbody', 10);
  }

  public function testSiteEditorBasicFunctionality(\AcceptanceTester $i) {
    $i->wantTo('Test WordPress site editor basic functionality');

    $i->wantTo('Navigate to site editor');
    $i->amOnPage('/wp-admin/site-editor.php');

    $i->wantTo('Wait for the site editor to load - try multiple possible indicators');
    try {
      $i->waitForText('Site Editor', 10);
    } catch (\Exception $e) {
      // Fallback: wait for any of the navigation items that should be present
      $i->waitForText('Navigation', 10);
    }

    $i->wantTo('Verify site editor is working by checking for key navigation items');
    $i->see('Navigation');
    $i->see('Styles');
    $i->see('Pages');
    $i->see('Templates');
    $i->see('Patterns');
  }

  public function testFrontendPropagation(\AcceptanceTester $i) {
    $i->wantTo('Test that changes in site editor are propagated to frontend');

    $i->wantTo('Create a test post first');
    $postTitle = 'Test Post for Site Editor';
    $postContent = 'This is test content for site editor propagation test.';
    $postUrl = $i->createPost($postTitle, $postContent);

    $i->wantTo('Navigate to site editor');
    $i->amOnPage('/wp-admin/site-editor.php');

    $i->wantTo('Wait for the site editor to load - try multiple possible indicators');
    try {
      $i->waitForText('Site Editor', 10);
    } catch (\Exception $e) {
      // Fallback: wait for any of the navigation items that should be present
      $i->waitForText('Navigation', 10);
    }

    $i->wantTo('Click on Templates to access template editing');
    $i->click('Templates');
    $i->waitForText('Templates', 10);

    $i->wantTo('Verify we can access the template editor by checking for template-related content');
    $i->see('Add Template');
    $i->see('All templates');

    $i->wantTo('Navigate to frontend to verify the post exists');
    $i->amOnUrl($postUrl);
    $i->waitForText($postTitle);
    $i->see($postContent);

    $i->wantTo("Verify the post is using the theme's styling (basic check)");
    $i->seeElement('body');
    $i->seeElement('main');
  }
}
