import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import {
  mainSidebarEmailKey,
  mainSidebarBlockKey,
  storeName,
} from '../../store';

type Props = {
  sidebarKey: string;
};

export function Header({ sidebarKey }: Props) {
  const { openSidebar } = useDispatch(storeName);

  const selectedBlockId = useSelect(
    (select) => select(blockEditorStore).getSelectedBlockClientId(),
    [],
  );

  // Switch tab based on selected block.
  useEffect(() => {
    void openSidebar(
      selectedBlockId ? mainSidebarBlockKey : mainSidebarEmailKey,
    );
  }, [selectedBlockId, openSidebar]);

  return (
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
      <ul>
        <li>
          <button
            onClick={() => {
              void openSidebar(mainSidebarEmailKey);
            }}
            className={classnames(
              'components-button edit-post-sidebar__panel-tab',
              { 'is-active': sidebarKey === mainSidebarEmailKey },
            )}
            data-automation-id="email_settings_tab"
            type="button"
          >
            {__('Email', 'mailpoet')}
          </button>
        </li>
        <li>
          <button
            onClick={() => {
              void openSidebar(mainSidebarBlockKey);
            }}
            className={classnames(
              'components-button edit-post-sidebar__panel-tab',
              { 'is-active': sidebarKey === mainSidebarBlockKey },
            )}
            data-automation-id="mailpoet_block_settings_tab"
            type="button"
          >
            {__('Block')}
          </button>
        </li>
      </ul>
    </div>
  );
}
