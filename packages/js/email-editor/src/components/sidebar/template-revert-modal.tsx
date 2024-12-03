import { __ } from '@wordpress/i18n';
import { Button, Flex, FlexItem, Modal } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';

export function TemplateRevertModal( { close } ) {
	const template = useSelect(
		( select ) => select( storeName ).getCurrentTemplate(),
		[]
	);
	const { revertAndSaveTemplate } = useDispatch( storeName );

	return (
		<Modal size="medium" onRequestClose={ close } __experimentalHideHeader>
			<p>
				{ __(
					'Reset to default and clear all customization?',
					'mailpoet'
				) }
			</p>
			<Flex justify={ 'end' }>
				<FlexItem>
					<Button variant="tertiary" onClick={ close }>
						{ __( 'Cancel', 'mailpoet' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="primary"
						onClick={ async () => {
							await revertAndSaveTemplate( template );
							close();
						} }
					>
						{ __( 'Reset', 'mailpoet' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
