import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Icon, layout } from '@wordpress/icons';
import { storeName } from '../../store';

export function TemplateInfo() {
	const template = useSelect(
		( select ) => select( storeName ).getCurrentTemplate(),
		[]
	);
	// @ts-expect-error Todo template type is not defined
	const description = template?.description || '';

	return (
		<Panel className="mailpoet-email-sidebar__email-type-info">
			<PanelBody>
				<PanelRow>
					<span className="mailpoet-email-type-info__icon">
						<Icon icon={ layout } />
					</span>
					<div className="mailpoet-email-type-info__content">
						<h2>
							{ /* @ts-expect-error Todo template type is not defined */ }
							{ template?.title || __( 'Template', 'mailpoet' ) }
						</h2>
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
	);
}
