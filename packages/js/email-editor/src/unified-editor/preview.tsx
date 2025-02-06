/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
// @ts-expect-error Missing types for PluginPreviewMenuItem
import { PluginPreviewMenuItem } from '@wordpress/editor'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { storeName } from '../store/constants';
import { SendPreviewEmail } from '../components/preview/send-preview-email';

export function SendPreview() {
	const { togglePreviewModal } = useDispatch( storeName );

	return (
		<>
			<PluginPreviewMenuItem
				icon={ external }
				onClick={ () => {
					togglePreviewModal( true );
				} }
			>
				{ __( 'Send preview', 'mailpoet' ) }
			</PluginPreviewMenuItem>
			<SendPreviewEmail />
		</>
	);
}
