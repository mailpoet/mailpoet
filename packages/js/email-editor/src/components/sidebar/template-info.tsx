import {
	Panel,
	PanelBody,
	PanelRow,
	DropdownMenu,
	MenuItem,
	Modal,
	Button,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Icon, layout, moreVertical } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { storeName } from '../../store';

export function TemplateInfo() {
	const template = useSelect(
		( select ) => select( storeName ).getCurrentTemplate(),
		[]
	);
	const [ isResetConfirmOpen, setResetConfirmOpen ] = useState( false );
	const { revertAndSaveTemplate } = useDispatch( storeName );

	// @ts-expect-error Todo template type is not defined
	const description = template?.description || '';
	const closeResetConfirm = () => setResetConfirmOpen( false );

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
									{ /* @ts-expect-error Todo template type is not defined */ }
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
				<Modal
					title={ __( 'Reset template to default', 'mailpoet' ) }
					size="medium"
					onRequestClose={ closeResetConfirm }
					__experimentalHideHeader
				>
					<p>
						{ __(
							'Reset to default and clear all customization?',
							'mailpoet'
						) }
					</p>
					<Flex justify={ 'end' }>
						<FlexItem>
							<Button
								variant="tertiary"
								onClick={ closeResetConfirm }
							>
								{ __( 'Cancel', 'mailpoet' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="primary"
								onClick={ async () => {
									await revertAndSaveTemplate( template );
									closeResetConfirm();
								} }
							>
								{ __( 'Reset', 'mailpoet' ) }
							</Button>
						</FlexItem>
					</Flex>
				</Modal>
			) }
		</>
	);
}
