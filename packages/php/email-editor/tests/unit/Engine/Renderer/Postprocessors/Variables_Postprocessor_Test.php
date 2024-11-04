<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Renderer\Postprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for Variables_Postprocessor
 */
class Variables_Postprocessor_Test extends \MailPoetUnitTest {
	/**
	 * Instance of Variables_Postprocessor
	 *
	 * @var Variables_Postprocessor
	 */
	private Variables_Postprocessor $postprocessor;

	/**
	 * Theme_Controller mock
	 *
	 * @var Theme_Controller & MockObject
	 */
	private $theme_controller_mock;

	/**
	 * Set up the test
	 */
	public function _before() {
		parent::_before();
		$this->theme_controller_mock = $this->createMock( Theme_Controller::class );
		$this->postprocessor         = new Variables_Postprocessor( $this->theme_controller_mock );
	}

	/**
	 * Test it replaces variables in the content
	 */
	public function testItReplacesVariablesInStyleAttributes(): void {
		$variables_map = array(
			'--wp--preset--spacing--10' => '10px',
			'--wp--preset--spacing--20' => '20px',
			'--wp--preset--spacing--30' => '30px',
		);
		$this->theme_controller_mock->method( 'get_variables_values_map' )->willReturn( $variables_map );
		$html   = '<div style="padding:var(--wp--preset--spacing--10);margin:var(--wp--preset--spacing--20)"><p style="color:white;padding-left:var(--wp--preset--spacing--10);">Helloo I have padding var(--wp--preset--spacing--10); </p></div>';
		$result = $this->postprocessor->postprocess( $html );
		$this->assertEquals( '<div style="padding:10px;margin:20px"><p style="color:white;padding-left:10px;">Helloo I have padding var(--wp--preset--spacing--10); </p></div>', $result );
	}
}
