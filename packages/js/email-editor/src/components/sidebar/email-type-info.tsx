/**
 * WordPress dependencies
 */
import {
	Panel,
	PanelBody,
	PanelRow,
	Flex,
	FlexItem,
	DropdownMenu,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, megaphone } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { EditTemplateModal } from './edit-template-modal';

export function EmailTypeInfo() {
	const template = useSelect(
		( select ) => select( storeName ).getCurrentTemplate(),
		[]
	);
	const [ isEditTemplateModalOpen, setEditTemplateModalOpen ] =
		useState( false );

	return (
		<>
			<Panel className="mailpoet-email-sidebar__email-type-info">
				<PanelBody>
					<PanelRow>
						<span className="mailpoet-email-type-info__icon">
							<Icon icon={ megaphone } />
						</span>
						<div className="mailpoet-email-type-info__content">
							<h2>{ __( 'Newsletter', 'mailpoet' ) }</h2>
							<span>
								{ __(
									'Send or schedule a newsletter to connect with your subscribers.',
									'mailpoet'
								) }
							</span>
						</div>
					</PanelRow>
					{ template && (
						<PanelRow>
							<Flex justify={ 'start' }>
								<FlexItem className="editor-post-panel__row-label">
									Template
								</FlexItem>
								<FlexItem>
									<DropdownMenu
										icon={ null }
										text={ template?.title }
										label={ __(
											'Template actions',
											'mailpoet'
										) }
									>
										{ ( { onClose } ) => (
											<>
												<MenuItem
													onClick={ () => {
														setEditTemplateModalOpen(
															true
														);
														onClose();
													} }
												>
													{ __(
														'Edit template',
														'mailpoet'
													) }
												</MenuItem>
												<MenuItem
													onClick={ () => {
														onClose();
													} }
												>
													{ __(
														'Swap template',
														'mailpoet'
													) }
												</MenuItem>
											</>
										) }
									</DropdownMenu>
								</FlexItem>
							</Flex>
						</PanelRow>
					) }
				</PanelBody>
			</Panel>
			{ isEditTemplateModalOpen && (
				<EditTemplateModal
					close={ () => setEditTemplateModalOpen( false ) }
				/>
			) }
		</>
	);
}
