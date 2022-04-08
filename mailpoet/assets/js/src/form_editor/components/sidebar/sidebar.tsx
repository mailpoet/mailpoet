import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import DefaultSidebar from './default_sidebar';
import PlacementSettingsSidebar from './placement_settings_sidebar';

export default function Sidebar(): JSX.Element {
  const { toggleSidebar, changeActiveSidebar } = useDispatch(
    'mailpoet-form-editor',
  );

  const activeSidebar = useSelect(
    (select) => select('mailpoet-form-editor').getActiveSidebar(),
    [],
  );

  const closePlacementSettings = (): void => {
    changeActiveSidebar('default');
  };

  const selectedBlockId = useSelect(
    (select) => select('core/block-editor').getSelectedBlockClientId(),
    [],
  );

  useEffect(() => {
    if (!selectedBlockId) {
      return;
    }
    changeActiveSidebar('default');
  }, [selectedBlockId, changeActiveSidebar]);

  return (
    <div className="edit-post-sidebar interface-complementary-area mailpoet_form_editor_sidebar">
      {activeSidebar === 'default' && (
        <DefaultSidebar onClose={(): void => toggleSidebar(false)} />
      )}
      {activeSidebar === 'placement_settings' && (
        <PlacementSettingsSidebar onClose={closePlacementSettings} />
      )}
    </div>
  );
}
