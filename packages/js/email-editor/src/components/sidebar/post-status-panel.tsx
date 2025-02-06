/**
 * External dependencies
 */
import {
	PanelRow,
	Flex,
	FlexItem,
	DropdownMenu,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { EditTemplateModal } from './edit-template-modal';
import { SelectTemplateModal } from '../template-select';
import { recordEvent } from '../../events';

export function PostStatusPanel() {
	const { template, currentEmailContent } = useSelect(
		( select ) => ( {
			template: select( storeName ).getCurrentTemplate(),
			currentEmailContent: select( storeName ).getEditedEmailContent(),
		} ),
		[]
	);
	const [ isEditTemplateModalOpen, setEditTemplateModalOpen ] =
		useState( false );
	const [ isSelectTemplateModalOpen, setSelectTemplateModalOpen ] =
		useState( false );

	return (
		<>
			{ template && (
				<PanelRow>
					<Flex justify={ 'start' }>
						<FlexItem className="editor-post-panel__row-label">
							{ __( 'Template', 'mailpoet' ) }
						</FlexItem>
						<FlexItem>
							<DropdownMenu
								icon={ null }
								text={ template?.title }
								toggleProps={ { variant: 'tertiary' } }
								label={ __( 'Template actions', 'mailpoet' ) }
								onToggle={ ( isOpen ) =>
									recordEvent(
										'sidebar_template_actions_clicked',
										{
											currentTemplate: template?.title,
											isOpen,
										}
									)
								}
							>
								{ ( { onClose } ) => (
									<>
										<MenuItem
											onClick={ () => {
												recordEvent(
													'sidebar_template_actions_edit_template_clicked'
												);
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
												recordEvent(
													'sidebar_template_actions_swap_template_clicked'
												);
												setSelectTemplateModalOpen(
													true
												);
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
			{ isEditTemplateModalOpen && (
				<EditTemplateModal
					close={ () => {
						recordEvent( 'edit_template_modal_closed' );
						return setEditTemplateModalOpen( false );
					} }
				/>
			) }
			{ isSelectTemplateModalOpen && (
				<SelectTemplateModal
					onSelectCallback={ () =>
						setSelectTemplateModalOpen( false )
					}
					closeCallback={ () => setSelectTemplateModalOpen( false ) }
					previewContent={ currentEmailContent }
				/>
			) }
		</>
	);
}
