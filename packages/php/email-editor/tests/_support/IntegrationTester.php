<?php
/**
 * This file is part of the MailPoet plugin tests.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types=1);

/**
 * Inherited Methods
 *
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
class IntegrationTester extends \Codeception\Actor {

	use _generated\IntegrationTesterActions;

	/**
	 * WP term ids.
	 *
	 * @var array
	 */
	private $wp_term_ids = array();

	/**
	 * List of created comment ids.
	 *
	 * @var array
	 */
	private $created_comment_ids = array();

	/**
	 * List of created posts.
	 *
	 * @var array
	 */
	private $posts = array();

	/**
	 * Method for creating a post.
	 *
	 * @param array $params - Post parameters.
	 * @return \WP_Post
	 * @throws \Exception - If the post creation fails.
	 */
	public function create_post( array $params ): \WP_Post {
		$post_id = wp_insert_post( $params );
		if ( $post_id instanceof WP_Error ) {
			throw new \Exception( 'Failed to create post' );
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			throw new \Exception( 'Failed to fetch the post' );
		}
		$this->posts[] = $post;
		return $post;
	}

	/**
	 * Method for cleaning up after the test.
	 */
	public function cleanup(): void {
		$this->delete_posts();
	}

	/**
	 * Delete created posts.
	 */
	private function delete_posts(): void {
		foreach ( $this->posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}
}
