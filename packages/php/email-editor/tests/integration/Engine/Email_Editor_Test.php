<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class Email_Editor_Test extends \MailPoetTest {
	/** @var Email_Editor */
	private $emailEditor;

	/** @var callable */
	private $postRegisterCallback;

	public function _before() {
		parent::_before();
		$this->emailEditor          = $this->di_container->get( Email_Editor::class );
		$this->postRegisterCallback = function ( $postTypes ) {
			$postTypes[] = array(
				'name' => 'custom_email_type',
				'args' => array(),
				'meta' => array(),
			);
			return $postTypes;
		};
		add_filter( 'mailpoet_email_editor_post_types', $this->postRegisterCallback );
		$this->emailEditor->initialize();
	}

	public function testItRegistersCustomPostTypeAddedViaHook() {
		$postTypes = get_post_types();
		$this->assertArrayHasKey( 'custom_email_type', $postTypes );
	}

	public function _after() {
		parent::_after();
		remove_filter( 'mailpoet_email_editor_post_types', $this->postRegisterCallback );
	}
}
