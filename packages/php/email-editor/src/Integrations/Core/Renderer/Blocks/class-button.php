<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Integrations\Utils\Dom_Document_Helper;

/**
 * Renders a button block.
 *
 * @see https://www.activecampaign.com/blog/email-buttons
 * @see https://documentation.mjml.io/#mj-button
 */
class Button extends Abstract_Block_Renderer {
	private function getWrapperStyles( array $blockStyles ) {
		$properties = array( 'border', 'color', 'typography', 'spacing' );
		$styles     = $this->getStylesFromBlock( array_intersect_key( $blockStyles, array_flip( $properties ) ) );
		return (object) array(
			'css'       => $this->compileCss(
				$styles['declarations'],
				array(
					'word-break' => 'break-word',
					'display'    => 'block',
				)
			),
			'classname' => $styles['classnames'],
		);
	}

	private function getLinkStyles( array $blockStyles ) {
		$styles = $this->getStylesFromBlock(
			array(
				'color'      => array(
					'text' => $blockStyles['color']['text'] ?? '',
				),
				'typography' => $blockStyles['typography'] ?? array(),
			)
		);
		return (object) array(
			'css'       => $this->compileCss( $styles['declarations'], array( 'display' => 'block' ) ),
			'classname' => $styles['classnames'],
		);
	}

	public function render( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		return $this->renderContent( $blockContent, $parsedBlock, $settingsController );
	}

	protected function renderContent( $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		if ( empty( $parsedBlock['innerHTML'] ) ) {
			return '';
		}

		$domHelper      = new Dom_Document_Helper( $parsedBlock['innerHTML'] );
		$blockClassname = $domHelper->getAttributeValueByTagName( 'div', 'class' ) ?? '';
		$buttonLink     = $domHelper->findElement( 'a' );

		if ( ! $buttonLink ) {
			return '';
		}

		$buttonText = $domHelper->getElementInnerHTML( $buttonLink ) ?: '';
		$buttonUrl  = $buttonLink->getAttribute( 'href' ) ?: '#';

		$blockAttributes = wp_parse_args(
			$parsedBlock['attrs'] ?? array(),
			array(
				'width'           => '',
				'style'           => array(),
				'textAlign'       => 'center',
				'backgroundColor' => '',
				'textColor'       => '',
			)
		);

		$blockStyles = array_replace_recursive(
			array(
				'color' => array_filter(
					array(
						'background' => $blockAttributes['backgroundColor'] ? $settingsController->translateSlugToColor( $blockAttributes['backgroundColor'] ) : null,
						'text'       => $blockAttributes['textColor'] ? $settingsController->translateSlugToColor( $blockAttributes['textColor'] ) : null,
					)
				),
			),
			$blockAttributes['style'] ?? array()
		);

		if ( ! empty( $blockStyles['border'] ) && empty( $blockStyles['border']['style'] ) ) {
			$blockStyles['border']['style'] = 'solid';
		}

		$wrapperStyles = $this->getWrapperStyles( $blockStyles );
		$linkStyles    = $this->getLinkStyles( $blockStyles );

		return sprintf(
			'<table border="0" cellspacing="0" cellpadding="0" role="presentation" style="width:%1$s;">
        <tr>
          <td align="%2$s" valign="middle" role="presentation" class="%3$s" style="%4$s">
            <a class="button-link %5$s" style="%6$s" href="%7$s" target="_blank">%8$s</a>
          </td>
        </tr>
      </table>',
			esc_attr( $blockAttributes['width'] ? '100%' : 'auto' ),
			esc_attr( $blockAttributes['textAlign'] ),
			esc_attr( $wrapperStyles->classname . ' ' . $blockClassname ),
			esc_attr( $wrapperStyles->css ),
			esc_attr( $linkStyles->classname ),
			esc_attr( $linkStyles->css ),
			esc_url( $buttonUrl ),
			$buttonText,
		);
	}
}
