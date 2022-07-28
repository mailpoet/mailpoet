import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { FormSettings } from 'form_editor/components/form_settings/form_settings';
import { BlockSettings } from './block_settings.jsx';
import { SidebarHeader } from './sidebar_header';

type Props = {
  onClose: () => void;
};

export function DefaultSidebar({ onClose }: Props): JSX.Element {
  const activeTab = useSelect(
    (select) => select('mailpoet-form-editor').getDefaultSidebarActiveTab(),
    [],
  );

  const selectedBlockId = useSelect(
    (select) => select('core/block-editor').getSelectedBlockClientId(),
    [],
  );

  const { switchDefaultSidebarTab } = useDispatch('mailpoet-form-editor');

  useEffect(() => {
    if (selectedBlockId) {
      void switchDefaultSidebarTab('block');
    } else {
      void switchDefaultSidebarTab('form');
    }
  }, [selectedBlockId, switchDefaultSidebarTab]);

  return (
    <>
      <SidebarHeader closeSidebar={onClose}>
        <ul>
          <li>
            <button
              onClick={(): void => {
                void switchDefaultSidebarTab('form');
              }}
              className={classnames(
                'components-button edit-post-sidebar__panel-tab',
                { 'is-active': activeTab === 'form' },
              )}
              data-automation-id="mailpoet_form_settings_tab"
              type="button"
            >
              {MailPoet.I18n.t('form')}
            </button>
          </li>
          <li>
            <button
              onClick={(): void => {
                void switchDefaultSidebarTab('block');
              }}
              className={classnames(
                'components-button edit-post-sidebar__panel-tab',
                { 'is-active': activeTab === 'block' },
              )}
              data-automation-id="mailpoet_block_settings_tab"
              type="button"
            >
              {__('Block')}
            </button>
          </li>
        </ul>
      </SidebarHeader>
      {activeTab === 'form' ? <FormSettings /> : <BlockSettings />}
    </>
  );
}
