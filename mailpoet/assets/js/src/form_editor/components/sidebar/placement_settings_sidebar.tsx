import { useSelect } from '@wordpress/data';
import { Panel, PanelBody } from '@wordpress/components';
import MailPoet from 'mailpoet';
import PlacementSettingsPanel from 'form_editor/components/form_settings/form_placement_options/settings_panel';
import SidebarHeader from './sidebar_header';

type Props = {
  onClose: () => void;
};

export default function PlaceMentSettingsSidebar({
  onClose,
}: Props): JSX.Element {
  const previewSettings = useSelect(
    (select) => select('mailpoet-form-editor').getPreviewSettings(),
    [],
  );
  return (
    <>
      <SidebarHeader closeSidebar={onClose}>
        <h3 className="mailpoet-sidebar-header-heading">
          {previewSettings.formType === 'others' &&
            MailPoet.I18n.t('placeFormOthers')}
          {previewSettings.formType === 'below_post' &&
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
          <PlacementSettingsPanel activePanel={previewSettings.formType} />
        </PanelBody>
      </Panel>
    </>
  );
}
