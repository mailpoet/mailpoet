import { Panel, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { SettingsPanel } from 'form-editor/components/form-settings/form-placement-options/settings-panel';
import { SidebarHeader } from './sidebar-header';
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
            __('Others (widget)', 'mailpoet')}
          {previewSettings.formType === 'below_posts' &&
            __('Below pages', 'mailpoet')}
          {previewSettings.formType === 'fixed_bar' &&
            __('Fixed bar', 'mailpoet')}
          {previewSettings.formType === 'popup' && __('Pop-up', 'mailpoet')}
          {previewSettings.formType === 'slide_in' &&
            __('Slide–in', 'mailpoet')}
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
