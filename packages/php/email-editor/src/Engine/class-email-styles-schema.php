<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Validator\Builder;

class Email_Styles_Schema {
	public function getSchema(): array {
		$typographyProps = Builder::object(
			array(
				'fontFamily'     => Builder::string()->nullable(),
				'fontSize'       => Builder::string()->nullable(),
				'fontStyle'      => Builder::string()->nullable(),
				'fontWeight'     => Builder::string()->nullable(),
				'letterSpacing'  => Builder::string()->nullable(),
				'lineHeight'     => Builder::string()->nullable(),
				'textTransform'  => Builder::string()->nullable(),
				'textDecoration' => Builder::string()->nullable(),
			)
		)->nullable();
		return Builder::object(
			array(
				'version' => Builder::integer(),
				'styles'  => Builder::object(
					array(
						'spacing'    => Builder::object(
							array(
								'padding'  => Builder::object(
									array(
										'top'    => Builder::string(),
										'right'  => Builder::string(),
										'bottom' => Builder::string(),
										'left'   => Builder::string(),
									)
								)->nullable(),
								'blockGap' => Builder::string()->nullable(),
							)
						)->nullable(),
						'color'      => Builder::object(
							array(
								'background' => Builder::string()->nullable(),
								'text'       => Builder::string()->nullable(),
							)
						)->nullable(),
						'typography' => $typographyProps,
						'elements'   => Builder::object(
							array(
								'heading' => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'button'  => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'link'    => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h1'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h2'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h3'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h4'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h5'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
								'h6'      => Builder::object(
									array(
										'typography' => $typographyProps,
									)
								)->nullable(),
							)
						)->nullable(),
					)
				)->nullable(),
			)
		)->toArray();
	}
}
