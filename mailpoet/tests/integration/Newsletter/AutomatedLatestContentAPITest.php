<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\API\JSON\v1\AutomatedLatestContent as ALCAPI;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Util\APIPermissionHelper;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContentAPITest extends \MailPoetTest {

  /*** @var array WP_User[] */
  private $createdUsers = [];

  /*** @var ALCAPI */
  private $alcAPI;

  /*** @var WPFunctions */
  private $wp;

  private static $alcBlock = [
    'type' => 'automatedLatestContentLayout',
    'withLayout' => true,
    'amount' => '2',
    'contentType' => 'post',
    'terms' => [],
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h2',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => true,
    'titlePosition' => 'abovePost',
    'featuredImagePosition' => 'left',
    'fullPostFeaturedImagePosition' => 'none',
    'showAuthor' => 'no',
    'authorPrecededBy' => 'Author:',
    'showCategories' => 'no',
    'categoriesPrecededBy' => 'Categories:',
    'readMoreType' => 'button',
    'readMoreText' => 'Read more',
    'readMoreButton' => [
      'type' => 'button',
      'text' => 'Read more',
      'url' => '[postLink]',
      'styles' => [
        'block' => [
          'backgroundColor' => '#e2973f',
          'borderColor' => '#e2973f',
          'borderWidth' => '0px',
          'borderRadius' => '5px',
          'borderStyle' => 'solid',
          'width' => '110px',
          'lineHeight' => '40px',
          'fontColor' => '#ffffff',
          'fontFamily' => 'Arial',
          'fontSize' => '14px',
          'fontWeight' => 'bold',
          'textAlign' => 'left',
        ],
      ],
      'context' => 'automatedLatestContentLayout.readMoreButton',
    ],
    'sortBy' => 'newest',
    'showDivider' => false,
    'divider' => [
      'type' => 'divider',
      'styles' => [
        'block' => [
          'backgroundColor' => 'transparent',
          'padding' => '13px',
          'borderStyle' => 'solid',
          'borderWidth' => '3px',
          'borderColor' => '#aaaaaa',
        ],
      ],
      'context' => 'automatedLatestContentLayout.divider',
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $loggerFactory = $this->diContainer->get(LoggerFactory::class);

    $alc = $this->make($this->diContainer->get(AutomatedLatestContent::class), [
      "loggerFactory" => $loggerFactory,
      "wp" => $this->wp,
      "transformPosts" => function ($block, $post) {
        return $post;
      },
    ]);
    $apiPermissionHelper = $this->diContainer->get(APIPermissionHelper::class);
    $this->alcAPI = new ALCAPI($alc, $apiPermissionHelper, $this->wp);

    if (is_multisite()) {
      // switch to the first blog in a network install, this should be removed when we add full support for MU
      switch_to_blog(1);
    }

    $this->deleteAllPosts();
  }

  public function _after() {
    parent::_after();

    // we've switched to blog_id=1
    if (is_multisite()) {
      restore_current_blog();
    }

    foreach ($this->createdUsers as $user) {
      wp_delete_user($user->ID);
    }

    $this->deleteAllPosts();
  }

  private function loginWithRole(string $role): \WP_User {
    $username = uniqid("testUser");
    $email = "$username@test.com";
    $existingUser = $this->wp->getUserBy("email", $email);

    if ($existingUser) {
      wp_delete_user($existingUser->ID);
    }

    wp_insert_user( [
      'user_login' => $username,
      'user_email' => $email,
      'user_pass' => '',
    ]);
    $user = $this->wp->getUserBy("email", $email);

    $user->add_role($role);

    $this->wp->wpSetCurrentUser($user->ID);
    $this->createdUsers[] = $user;

    return $user;
  }

  private function deleteAllPosts() {
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE $wpdb->posts");
  }

  public function testGetBulkTransformedPosts() {
    $publishedPostTitle = 'Published Post';
    wp_insert_post([
      'post_title' => 'Private Post',
      'post_content' => 'contents',
      'post_status' => 'private',
    ]);
    wp_insert_post([
      'post_title' => $publishedPostTitle,
      'post_content' => 'contents',
      'post_status' => 'publish',
    ]);

    $singleBlockQuery = array_merge(self::$alcBlock, ['postStatus' => "any"]);
    $result = $this->alcAPI->getBulkTransformedPosts([
      "blocks" => [$singleBlockQuery],
    ]);
    expect($result->data)->count(1);

    expect($result->data[0][0]->post_title)->equals($publishedPostTitle);

    $this->loginWithRole("editor");
    $result = $this->alcAPI->getBulkTransformedPosts([
      "blocks" => [$singleBlockQuery],
    ]);
    expect($result->data)->count(1);
    expect($result->data[0][0]->post_title)->equals($publishedPostTitle);

    $this->loginWithRole("administrator");
    $result = $this->alcAPI->getBulkTransformedPosts([
      "blocks" => [$singleBlockQuery],
    ]);
    expect($result->data)->count(1);
    expect($result->data[0][0]->post_title)->equals($publishedPostTitle);
  }

  /**
   * @param \WP_Post[] $posts
   * @return string[]
   */
  private function getPostTitles($posts): array {
    return array_map(function (\WP_Post $post) {
      return $post->post_title;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }, $posts);
  }

  public function testGetPosts() {
    $privatePost = [
      'post_title' => 'Private Post',
      'post_content' => 'contents',
      'post_status' => 'private',
    ];
    $publicPost = [
      'post_title' => 'Published Post',
      'post_content' => 'contents',
      'post_status' => 'publish',
    ];
    $draftPost = [
      'post_title' => 'Draft Post',
      'post_content' => 'contents',
      'post_status' => 'draft',
    ];

    array_map(function ($postarr){
      wp_insert_post($postarr);
    }, [$privatePost, $publicPost, $draftPost]);

    $result = $this->alcAPI->getPosts(['postStatus' => "any", "contentType" => "post"]);

    $this->loginWithRole("reader");
    expect($result->data)->count(1);
    expect($this->getPostTitles($result->data))->contains($publicPost['post_title']);


    $this->loginWithRole("editor");
    $result = $this->alcAPI->getPosts(['postStatus' => "any", "contentType" => "post"]);
    expect($result->data)->count(2);
    expect($this->getPostTitles($result->data))->contains($publicPost["post_title"]);
    expect($this->getPostTitles($result->data))->contains($privatePost["post_title"]);

    $user = $this->loginWithRole("administrator");
    if (is_multisite()) {
      grant_super_admin($user->ID);
    }

    $result = $this->alcAPI->getPosts(['postStatus' => "any", "contentType" => "post"]);
    expect($result->data)->count(3);
    expect($this->getPostTitles($result->data))->contains($publicPost["post_title"]);
    expect($this->getPostTitles($result->data))->contains($draftPost["post_title"]);
    expect($this->getPostTitles($result->data))->contains($privatePost["post_title"]);
  }
}
