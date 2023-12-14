import { useDispatch, useSelect } from '@wordpress/data';
import { partial } from 'lodash';
import { ErrorBoundary } from 'common';
import { BasicSettingsPanel } from './basic-settings-panel';
import { StylesSettingsPanel } from './styles-settings-panel';
import { FormPlacementPanel } from './form-placement-panel';
import { CustomCssPanel } from './custom-css-panel';
import { TagsPanel } from './tags-panel';
import { storeName } from '../../store';

export function FormSettings(): JSX.Element {
  const { toggleSidebarPanel } = useDispatch(storeName);
  const openedPanels = useSelect(
    (select) => select(storeName).getSidebarOpenedPanels(),
    [],
  );

  return (
    <>
      <ErrorBoundary>
        <BasicSettingsPanel
          isOpened={openedPanels.includes('basic-settings')}
          onToggle={partial(toggleSidebarPanel, 'basic-settings')}
        />
      </ErrorBoundary>
      <StylesSettingsPanel
        isOpened={openedPanels.includes('styles-settings')}
        onToggle={partial(toggleSidebarPanel, 'styles-settings')}
      />
      <FormPlacementPanel
        isOpened={openedPanels.includes('form-placement')}
        onToggle={partial(toggleSidebarPanel, 'form-placement')}
      />
      <TagsPanel
        isOpened={openedPanels.includes('tags')}
        onToggle={partial(toggleSidebarPanel, 'tags')}
      />
      <CustomCssPanel
        isOpened={openedPanels.includes('custom-css')}
        onToggle={partial(toggleSidebarPanel, 'custom-css')}
      />
    </>
  );
}
