import { Panel, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { SettingsPanel } from 'form_editor/components/form_settings/form_placement_options/settings_panel';
import { SidebarHeader } from './sidebar_header';
import { storeName } from '../../store';

type Props = {
  onClose: () => void;
};

export function PlacementSettingsSidebar({ onClose }: Props): JSX.Element {
  const previewSettings = useSelect(
    (select) => select(storeName).getPreviewSettings(),
    [],
  );
  return (
    <>
      <SidebarHeader closeSidebar={onClose}>
        <h3 className="mailpoet-sidebar-header-heading">
          {previewSettings.formType === 'others' &&
            MailPoet.I18n.t('placeFormOthers')}
          {previewSettings.formType === 'below_posts' &&
            MailPoet.I18n.t('placeFormBellowPages')}
          {previewSettings.formType === 'fixed_bar' &&
            MailPoet.I18n.t('placeFixedBarFormOnPages')}
          {previewSettings.formType === 'popup' &&
            MailPoet.I18n.t('placePopupFormOnPages')}
          {previewSettings.formType === 'slide_in' &&
            MailPoet.I18n.t('placeSlideInFormOnPages')}
        </h3>
      </SidebarHeader>
      <Panel>
        <PanelBody>
          <SettingsPanel activePanel={previewSettings.formType} />
        </PanelBody>
      </Panel>
    </>
  );
}
