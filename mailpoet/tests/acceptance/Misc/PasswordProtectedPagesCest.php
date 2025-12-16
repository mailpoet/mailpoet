<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

/**
 * @group frontend
 * Ensures password-protected pages do not expose content when MailPoet is active
 */
class PasswordProtectedPagesCest {

  private const PASSWORD = 'testpassword123';
  private const PROTECTED_CONTENT = 'This is secret protected content that should not be visible without password.';

  public function passwordProtectedPostContentIsNotExposed(\AcceptanceTester $i) {
    $i->wantTo('Test that password-protected post content is not exposed');

    $i->login();

    $postTitle = 'Protected Post Test';
    $postUrl = $i->createPasswordProtectedPost($postTitle, self::PROTECTED_CONTENT, self::PASSWORD, 'post');

    $i->wantTo('Log out to test as anonymous user');
    $i->logOut();

    $i->wantTo('Visit the password-protected post as an anonymous user');
    $i->amOnUrl($postUrl);

    $i->wantTo('Verify the password form is displayed');
    $i->waitForText('Protected:', 10);
    $i->seeElement('input[name="post_password"]');

    $i->wantTo('Verify the protected content is NOT visible');
    $i->dontSee(self::PROTECTED_CONTENT);

    $i->wantTo('Enter the password and verify content becomes visible');
    $i->fillField('input[name="post_password"]', self::PASSWORD);
    $i->click('input[type="submit"]');
    $i->waitForText(self::PROTECTED_CONTENT, 10);
    $i->see(self::PROTECTED_CONTENT);
  }

  public function passwordProtectedPageContentIsNotExposed(\AcceptanceTester $i) {
    $i->wantTo('Test that password-protected page content is not exposed');

    $i->login();

    $pageTitle = 'Protected Page Test';
    $pageUrl = $i->createPasswordProtectedPost($pageTitle, self::PROTECTED_CONTENT, self::PASSWORD, 'page');

    $i->wantTo('Log out to test as anonymous user');
    $i->logOut();

    $i->wantTo('Visit the password-protected page as an anonymous user');
    $i->amOnUrl($pageUrl);

    $i->wantTo('Verify the password form is displayed');
    $i->waitForText('Protected:', 10);
    $i->seeElement('input[name="post_password"]');

    $i->wantTo('Verify the protected content is NOT visible');
    $i->dontSee(self::PROTECTED_CONTENT);
  }

  public function passwordProtectionWorksWithWooCommerceActive(\AcceptanceTester $i) {
    $i->wantTo('Test that password protection works when WooCommerce is active');

    $i->login();

    $i->wantTo('Activate WooCommerce plugin');
    $i->activateWooCommerce();

    $postTitle = 'Protected Post With WooCommerce';
    $protectedContent = 'Secret content with WooCommerce active, this should not be visible.';
    $postUrl = $i->createPasswordProtectedPost($postTitle, $protectedContent, self::PASSWORD, 'post');

    $i->wantTo('Log out to test as anonymous user');
    $i->logOut();

    $i->wantTo('Visit the password-protected post as an anonymous user');
    $i->amOnUrl($postUrl);

    $i->wantTo('Verify the password form is displayed');
    $i->waitForText('Protected:', 10);
    $i->seeElement('input[name="post_password"]');

    $i->wantTo('Verify the protected content is NOT visible');
    $i->dontSee($protectedContent);

    $i->wantTo('Enter the password and verify content becomes visible');
    $i->fillField('input[name="post_password"]', self::PASSWORD);
    $i->click('input[type="submit"]');
    $i->waitForText($protectedContent, 10);
    $i->see($protectedContent);

    $i->wantTo('Deactivate WooCommerce plugin');
    $i->deactivateWooCommerce();
  }

  public function multiplePasswordProtectedPostsDontLeakContent(\AcceptanceTester $i) {
    $i->wantTo('Test that visiting multiple password-protected posts does not leak content');

    $i->login();

    $posts = [];
    for ($j = 1; $j <= 3; $j++) {
      $title = "Protected Post $j";
      $content = "Secret content for post $j, this must remain hidden.";
      $posts[] = [
        'title' => $title,
        'content' => $content,
        'url' => $i->createPasswordProtectedPost($title, $content, self::PASSWORD, 'post'),
      ];
    }

    $i->wantTo('Log out to test as anonymous user');
    $i->logOut();

    $i->wantTo('Visit each password-protected post and verify content is not exposed');
    foreach ($posts as $post) {
      $i->amOnUrl($post['url']);
      $i->waitForText('Protected:', 10);
      $i->seeElement('input[name="post_password"]');
      $i->dontSee($post['content']);
    }

    $i->wantTo('Visit them again in reverse order to ensure no caching issues');
    foreach (array_reverse($posts) as $post) {
      $i->amOnUrl($post['url']);
      $i->waitForText('Protected:', 10);
      $i->dontSee($post['content']);
    }
  }
}
