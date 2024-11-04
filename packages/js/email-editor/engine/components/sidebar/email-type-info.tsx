import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, megaphone } from '@wordpress/icons';

export function EmailTypeInfo() {
  return (
    <Panel className="mailpoet-email-sidebar__email-type-info">
      <PanelBody>
        <PanelRow>
          <span className="mailpoet-email-type-info__icon">
            <Icon icon={megaphone} />
          </span>
          <div className="mailpoet-email-type-info__content">
            <h2>{__('Newsletter', 'mailpoet')}</h2>
            <span>
              {__(
                'Send or schedule a newsletter to connect with your subscribers.',
                'mailpoet',
              )}
            </span>
          </div>
        </PanelRow>
      </PanelBody>
    </Panel>
  );
}
