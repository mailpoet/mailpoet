<?php declare(strict_types=1);


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class IntegrationTester extends \Codeception\Actor
{
  use _generated\IntegrationTesterActions;

  private $wpTermIds = [];

  private $createdCommentIds = [];

  private $posts = [];

  public function createPost(array $params): \WP_Post {
    $postId = wp_insert_post($params);
    if ($postId instanceof WP_Error) {
      throw new \Exception('Failed to create post');
    }
    $post = get_post($postId);
    if (!$post instanceof WP_Post) {
      throw new \Exception('Failed to fetch the post');
    }
    $this->posts[] = $post;
    return $post;
  }

  public function cleanup() {
    $this->deletePosts();
  }

  private function deletePosts() {
    foreach ($this->posts as $post) {
      wp_delete_post($post->ID, true);
    }
  }
}
