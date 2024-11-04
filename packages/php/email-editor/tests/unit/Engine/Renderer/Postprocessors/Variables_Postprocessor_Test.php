<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Postprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use PHPUnit\Framework\MockObject\MockObject;

class Variables_Postprocessor_Test extends \MailPoetUnitTest {
	private Variables_Postprocessor $postprocessor;

	/** @var Theme_Controller & MockObject */
	private $themeControllerMock;

	public function _before() {
		parent::_before();
		$this->themeControllerMock = $this->createMock( Theme_Controller::class );
		$this->postprocessor       = new Variables_Postprocessor( $this->themeControllerMock );
	}

	public function testItReplacesVariablesInStyleAttributes(): void {
		$variablesMap = array(
			'--wp--preset--spacing--10' => '10px',
			'--wp--preset--spacing--20' => '20px',
			'--wp--preset--spacing--30' => '30px',
		);
		$this->themeControllerMock->method( 'get_variables_values_map' )->willReturn( $variablesMap );
		$html   = '<div style="padding:var(--wp--preset--spacing--10);margin:var(--wp--preset--spacing--20)"><p style="color:white;padding-left:var(--wp--preset--spacing--10);">Helloo I have padding var(--wp--preset--spacing--10); </p></div>';
		$result = $this->postprocessor->postprocess( $html );
		$this->assertEquals( '<div style="padding:10px;margin:20px"><p style="color:white;padding-left:10px;">Helloo I have padding var(--wp--preset--spacing--10); </p></div>', $result );
	}
}
