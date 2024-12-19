import {
	MenuGroup,
	MenuItem,
	Button,
	DropdownMenu,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, external, check, mobile, desktop } from '@wordpress/icons';
import { SendPreviewEmail } from './send-preview-email';
import { storeName } from '../../store';
import { recordEvent } from '../../events';

export function PreviewDropdown() {
	const [ mailpoetEmailData ] = useEntityProp(
		'postType',
		'mailpoet_email',
		'mailpoet_data'
	);

	const previewDeviceType = useSelect(
		( select ) => select( storeName ).getDeviceType(),
		[]
	);

	const { changePreviewDeviceType, togglePreviewModal } =
		useDispatch( storeName );
	const newsletterPreviewUrl: string = mailpoetEmailData?.preview_url || '';

	const changeDeviceType = ( newDeviceType: string ) => {
		void changePreviewDeviceType( newDeviceType );
	};

	const openInNewTab = ( url: string ) => {
		window.open( url, '_blank', 'noreferrer' );
	};

	const deviceIcons = {
		mobile,
		desktop,
	};

	return (
		<>
			<DropdownMenu
				className="mailpoet-preview-dropdown"
				label={ __( 'Preview', 'mailpoet' ) }
				icon={ deviceIcons[ previewDeviceType.toLowerCase() ] }
				onToggle={ ( isOpened ) =>
					recordEvent( 'header_preview_dropdown_clicked', {
						isOpened,
					} )
				}
			>
				{ ( { onClose } ) => (
					<>
						<MenuGroup>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									changeDeviceType( 'Desktop' );
									recordEvent(
										'header_preview_dropdown_desktop_selected'
									);
								} }
								icon={
									previewDeviceType === 'Desktop' && check
								}
							>
								{ __( 'Desktop', 'mailpoet' ) }
							</MenuItem>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									changeDeviceType( 'Mobile' );
									recordEvent(
										'header_preview_dropdown_mobile_selected'
									);
								} }
								icon={ previewDeviceType === 'Mobile' && check }
							>
								{ __( 'Mobile', 'mailpoet' ) }
							</MenuItem>
						</MenuGroup>
						<MenuGroup>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									void togglePreviewModal( true );
									recordEvent(
										'header_preview_dropdown_send_test_email_selected'
									);
									onClose();
								} }
							>
								{ __( 'Send a test email', 'mailpoet' ) }
							</MenuItem>
						</MenuGroup>
						{ newsletterPreviewUrl ? (
							<MenuGroup>
								<div className="edit-post-header-preview__grouping-external">
									<Button
										className="edit-post-header-preview__button-external components-menu-item__button"
										onClick={ () => {
											recordEvent(
												'header_preview_dropdown_preview_in_new_tab_selected'
											);
											openInNewTab(
												newsletterPreviewUrl
											);
										} }
									>
										{ __(
											'Preview in new tab',
											'mailpoet'
										) }
										<Icon icon={ external } />
									</Button>
								</div>
							</MenuGroup>
						) : null }
					</>
				) }
			</DropdownMenu>
			<SendPreviewEmail />
		</>
	);
}
