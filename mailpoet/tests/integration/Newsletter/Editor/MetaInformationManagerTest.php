<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\MetaInformationManager;
use MailPoet\WP\Functions as WPFunctions;

class MetaInformationManagerTest extends \MailPoetTest {
  private const AUTHOR_FILTER = 'mailpoet_newsletter_post_author';
  private const CATEGORIES_FILTER = 'mailpoet_newsletter_post_categories';

  /** @var MetaInformationManager */
  private $metaManager;

  /** @var WPFunctions */
  private $wp;

  /** @var int */
  private $authorId;

  /** @var int */
  private $postId;

  /** @var array */
  private $args;

  /** @var array<array{string, callable}> */
  private $registeredFilters = [];

  public function _before() {
    parent::_before();
    $this->metaManager = new MetaInformationManager();
    $this->wp = new WPFunctions();

    $authorId = wp_insert_user([
      'user_login' => 'stomail8385',
      'user_email' => 'stomail8385@example.com',
      'user_pass' => 'password',
      'display_name' => 'Original Author',
    ]);
    $this->assertIsInt($authorId);
    $this->authorId = $authorId;

    $this->postId = (int)$this->wp->wpInsertPost([
      'post_title' => 'Post with meta information',
      'post_content' => 'Body',
      'post_status' => 'publish',
      'post_type' => 'post',
      'post_author' => $this->authorId,
    ]);
    wp_set_post_terms($this->postId, ['Announcements'], 'category');

    $this->args = [
      'showAuthor' => 'belowText',
      'authorPrecededBy' => 'Author:',
      'showCategories' => 'belowText',
      'categoriesPrecededBy' => 'Categories:',
    ];
  }

  public function testItRendersTheAuthorAndCategoriesWhenNoFilterIsRegistered() {
    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Author: Original Author');
    verify($content)->stringContainsString('Categories: Announcements');
  }

  public function testItLetsAFilterReplaceTheAuthor() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return 'Guest Author';
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Guest Author');
    verify($content)->stringNotContainsString('Original Author');
    verify($content)->stringContainsString('Categories: Announcements');
  }

  public function testItLetsAFilterReplaceTheCategories() {
    $this->addFilter(self::CATEGORIES_FILTER, function () {
      return 'Filed under: Politics';
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Filed under: Politics');
    verify($content)->stringNotContainsString('Announcements');
    verify($content)->stringContainsString('Author: Original Author');
  }

  public function testItPassesThePostIdAndAuthorIdToTheAuthorFilter() {
    $received = [];
    $this->addFilter(self::AUTHOR_FILTER, function ($author, $postId, $postAuthor) use (&$received) {
      $received = [$author, $postId, $postAuthor];
      return $author;
    }, 3);

    $this->appendMetaInformation();

    verify($received[0])->equals('Author: Original Author');
    verify((int)$received[1])->equals($this->postId);
    verify((int)$received[2])->equals($this->authorId);
  }

  public function testItPassesThePostIdAndPostTypeToTheCategoriesFilter() {
    $received = [];
    $this->addFilter(self::CATEGORIES_FILTER, function ($categories, $postId, $postType) use (&$received) {
      $received = [$categories, $postId, $postType];
      return $categories;
    }, 3);

    $this->appendMetaInformation();

    verify($received[0])->equals('Categories: Announcements');
    verify((int)$received[1])->equals($this->postId);
    verify($received[2])->equals('post');
  }

  public function testItKeepsTheOriginalAuthorWhenAFilterReturnsAnArray() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return ['Guest Author'];
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Author: Original Author');
    verify($content)->stringNotContainsString('Array');
  }

  public function testItKeepsTheOriginalAuthorWhenAFilterReturnsAnObject() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return new \stdClass();
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Author: Original Author');
  }

  public function testItKeepsTheOriginalAuthorWhenAFilterReturnsNull() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return null;
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Author: Original Author');
  }

  public function testItRendersANumericAuthorReturnedByAFilter() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return 42;
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('42');
    verify($content)->stringNotContainsString('Original Author');
  }

  public function testItAppliesTheAuthorFilterWhenThePostHasNoAuthor() {
    $received = [];
    $this->addFilter(self::AUTHOR_FILTER, function ($author, $postId, $postAuthor) use (&$received) {
      $received = [$postId, $postAuthor];
      return 'Guest Author';
    }, 3);

    $post = (object)[
      'ID' => $this->postId,
      'post_author' => null,
      'post_type' => 'post',
    ];
    $content = $this->metaManager->appendMetaInformation('<p>Body</p>', $post, $this->args);

    verify($content)->stringContainsString('Guest Author');
    verify($received[1])->null();
  }

  public function testItJoinsTheAuthorAndCategoriesFilterResults() {
    $this->addFilter(self::AUTHOR_FILTER, function () {
      return 'Guest Author';
    });
    $this->addFilter(self::CATEGORIES_FILTER, function () {
      return 'Politics';
    });

    $content = $this->appendMetaInformation();

    verify($content)->stringContainsString('Guest Author<br />Politics');
  }

  public function _after() {
    foreach ($this->registeredFilters as [$name, $callback]) {
      $this->wp->removeFilter($name, $callback, 10);
    }
    $this->registeredFilters = [];
    $this->wp->wpDeletePost($this->postId, true);
    wp_delete_user($this->authorId);
    parent::_after();
  }

  private function addFilter(string $name, callable $callback, int $acceptedArgs = 1): void {
    $this->wp->addFilter($name, $callback, 10, $acceptedArgs);
    $this->registeredFilters[] = [$name, $callback];
  }

  private function appendMetaInformation(): string {
    return $this->metaManager->appendMetaInformation('<p>Body</p>', $this->getPost(), $this->args);
  }

  private function getPost(): \WP_Post {
    $post = $this->wp->getPost($this->postId);
    $this->assertInstanceOf(\WP_Post::class, $post);
    return $post;
  }
}
