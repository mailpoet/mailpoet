<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

/**
 * Integration test for User Theme class
 */
class User_Theme_Test extends \MailPoetTest {
	/**
	 * Instance of User_Theme created before each test.
	 *
	 * @var User_Theme
	 */
	private User_Theme $user_theme;

	/**
	 * Set up before each test.
	 */
	protected function _before(): void {
		parent::_before();
		$this->user_theme = new User_Theme();
	}

	/**
	 * Test it can create and return a user theme post
	 */
	public function testItCreatesUserThemePostLazily(): void {
		$post = $this->user_theme->get_user_theme_post();
		$this->assertInstanceOf( \WP_Post::class, $post );
		$post_content = json_decode( $post->post_content, true );
		$this->assertIsArray( $post_content );
		$this->assertArrayHasKey( 'version', $post_content );
		$this->assertEquals( 3, $post_content['version'] );
		$this->assertArrayHasKey( 'isGlobalStylesUserThemeJSON', $post_content );
		$this->assertTrue( $post_content['isGlobalStylesUserThemeJSON'] );
	}

	/**
	 * Test it fetches previously stored data
	 */
	public function testItFetchesPreviouslyStoredData(): void {
		$styles_data = array(
			'version'                     => 3,
			'isGlobalStylesUserThemeJSON' => true,
			'styles'                      => array(
				'color' => array(
					'background' => '#000000',
					'text'       => '#ffffff',
				),
			),
		);
		$post_data   = array(
			'post_title'   => __( 'Custom Email Styles', 'mailpoet' ),
			'post_name'    => 'wp-global-styles-mailpoet-email',
			'post_content' => (string) wp_json_encode( $styles_data, JSON_FORCE_OBJECT ),
			'post_status'  => 'publish',
			'post_type'    => 'wp_global_styles',
		);
		wp_insert_post( $post_data );

		$post = $this->user_theme->get_user_theme_post();
		$this->assertInstanceOf( \WP_Post::class, $post );
		$post_content = json_decode( $post->post_content, true );
		$this->assertIsArray( $post_content );
		$this->assertArrayHasKey( 'version', $post_content );
		$this->assertEquals( 3, $post_content['version'] );
		$this->assertArrayHasKey( 'isGlobalStylesUserThemeJSON', $post_content );
		$this->assertTrue( $post_content['isGlobalStylesUserThemeJSON'] );
		$this->assertEquals( $styles_data['styles'], $post_content['styles'] );
	}

	/**
	 * Test it returns the user WP_Theme_JSON
	 */
	public function testItCreatesThemeJson(): void {
		$theme = $this->user_theme->get_theme();
		$this->assertInstanceOf( \WP_Theme_JSON::class, $theme );
		$raw = $theme->get_raw_data();
		$this->assertArrayHasKey( 'version', $raw );
	}
}
