/**
 * External dependencies
 */
import {
	Panel,
	PanelBody,
	PanelRow,
	DropdownMenu,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Icon, layout, moreVertical } from '@wordpress/icons';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { TemplateRevertModal } from './template-revert-modal';

export function TemplateInfo() {
	const template = useSelect(
		( select ) => select( storeName ).getCurrentTemplate(),
		[]
	);
	const [ isResetConfirmOpen, setResetConfirmOpen ] = useState( false );

	// @ts-expect-error Todo template type is not defined
	const description = template?.description || '';

	return (
		<>
			<Panel className="mailpoet-email-sidebar__email-type-info">
				<PanelBody>
					<PanelRow>
						<span className="mailpoet-email-type-info__icon">
							<Icon icon={ layout } />
						</span>
						<div className="mailpoet-email-type-info__content">
							<div className="mailpoet-email-type-info__content_heading">
								<h2>
									{ template?.title ||
										__( 'Template', 'mailpoet' ) }
								</h2>
								<DropdownMenu
									icon={ moreVertical }
									label={ __(
										'Template actions',
										'mailpoet'
									) }
								>
									{ ( { onClose } ) => (
										<MenuItem
											onClick={ () => {
												setResetConfirmOpen( true );
												onClose();
											} }
											info={ __(
												'Reset to default to clear all customizations',
												'mailpoet'
											) }
										>
											{ __( 'Reset', 'mailpoet' ) }
										</MenuItem>
									) }
								</DropdownMenu>
							</div>
							{ description && <p>{ description || '' }</p> }
							<p>
								{ __(
									'Edit this template to be used across multiple emails.',
									'mailpoet'
								) }
							</p>
						</div>
					</PanelRow>
				</PanelBody>
			</Panel>
			{ isResetConfirmOpen && (
				<TemplateRevertModal
					close={ () => {
						setResetConfirmOpen( false );
					} }
				/>
			) }
		</>
	);
}
