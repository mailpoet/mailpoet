import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { DefaultSidebar } from './default-sidebar';
import { PlacementSettingsSidebar } from './placement-settings-sidebar';
import { storeName } from '../../store';

function Sidebar(): JSX.Element {
  const { toggleSidebar, changeActiveSidebar } = useDispatch(storeName);

  const { activeSidebar, selectedBlockId } = useSelect(
    (select) => ({
      activeSidebar: select(storeName).getActiveSidebar(),
      selectedBlockId: select('core/block-editor').getSelectedBlockClientId(),
    }),
    [],
  );

  const closePlacementSettings = (): void => {
    void changeActiveSidebar('default');
  };

  useEffect(() => {
    if (!selectedBlockId) {
      return;
    }
    void changeActiveSidebar('default');
  }, [selectedBlockId, changeActiveSidebar]);

  return (
    <div className="edit-post-sidebar interface-complementary-area mailpoet_form_editor_sidebar">
      {activeSidebar === 'default' && (
        <DefaultSidebar
          onClose={(): void => {
            void toggleSidebar(false);
          }}
        />
      )}
      {activeSidebar === 'placement_settings' && (
        <PlacementSettingsSidebar onClose={closePlacementSettings} />
      )}
    </div>
  );
}

Sidebar.displayName = 'FormEditorSidebar';
export { Sidebar };
