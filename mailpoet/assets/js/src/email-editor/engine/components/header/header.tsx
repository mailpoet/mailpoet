import { useRef, useState } from '@wordpress/element';
import { PinnedItems } from '@wordpress/interface';
import { Button, ToolbarItem as WpToolbarItem } from '@wordpress/components';
import {
  NavigableToolbar,
  BlockToolbar as WPBlockToolbar,
  store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
// @ts-expect-error DocumentBar types are not available
import { DocumentBar, store as editorStore } from '@wordpress/editor';
import { store as preferencesStore } from '@wordpress/preferences';
import { __ } from '@wordpress/i18n';
import { plus, listView, undo, redo, next, previous } from '@wordpress/icons';
import classnames from 'classnames';
import { storeName } from '../../store';
import { MoreMenu } from './more-menu';
import { PreviewDropdown } from '../preview';
import { SaveButton } from './save-button';
import { CampaignName } from './campaign-name';
import { SendButton } from './send-button';

import { unlock } from '../../../lock-unlock';

// Build type for ToolbarItem contains only "as" and "children" properties but it takes all props from
// component passed to "as" property (in this case Button). So as fix for TS errors we need to pass all props from Button to ToolbarItem.
// We should be able to remove this fix when ToolbarItem will be fixed in Gutenberg.
const ToolbarItem = WpToolbarItem as React.ForwardRefExoticComponent<
  React.ComponentProps<typeof WpToolbarItem> &
    React.ComponentProps<typeof Button>
>;

// Definition of BlockToolbar in currently installed Gutenberg packages (wp-6.4) is missing hideDragHandle prop
// After updating to newer version of Gutenberg we should be able to remove this fix
const BlockToolbar = WPBlockToolbar as React.FC<
  React.ComponentProps<typeof WPBlockToolbar> & {
    hideDragHandle?: boolean;
  }
>;

export function Header() {
  const inserterButton = useRef();
  const listviewButton = useRef();
  const undoButton = useRef();
  const redoButton = useRef();

  const [isBlockToolsCollapsed, setIsBlockToolsCollapsed] = useState(false);

  const { toggleInserterSidebar, toggleListviewSidebar } =
    useDispatch(storeName);
  const { undo: undoAction, redo: redoAction } = useDispatch(coreDataStore);
  const {
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    isFixedToolbarActive,
    isBlockSelected,
    hasUndo,
    hasRedo,
    hasDocumentNavigationHistory,
  } = useSelect((select) => {
    // eslint-disable-next-line @typescript-eslint/naming-convention
    const { getEditorSettings: _getEditorSettings } = unlock(
      select(editorStore),
    );
    const editorSettings = _getEditorSettings();

    return {
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      isFixedToolbarActive: select(preferencesStore).get(
        'core',
        'fixedToolbar',
      ),
      isBlockSelected: !!select(blockEditorStore).getBlockSelectionStart(),
      hasUndo: select(coreDataStore).hasUndo(),
      hasRedo: select(coreDataStore).hasRedo(),
      hasDocumentNavigationHistory:
        !!editorSettings.onNavigateToPreviousEntityRecord,
    };
  }, []);

  const preventDefault = (event) => {
    event.preventDefault();
  };

  const shortLabelInserter = !isInserterSidebarOpened
    ? __('Add', 'mailpoet')
    : __('Close', 'mailpoet');

  return (
    <div className="editor-header edit-post-header">
      <div className="editor-header__toolbar edit-post-header__toolbar">
        <NavigableToolbar
          className="editor-header__toolbar edit-post-header-toolbar is-unstyled editor-document-tools"
          aria-label={__('Email document tools', 'mailpoet')}
        >
          <div className="editor-document-tools__left">
            <ToolbarItem
              ref={inserterButton}
              as={Button}
              className="editor-header-toolbar__inserter-toggle edit-post-header-toolbar__inserter-toggle"
              variant="primary"
              isPressed={isInserterSidebarOpened}
              onMouseDown={preventDefault}
              onClick={toggleInserterSidebar}
              disabled={false}
              icon={plus}
              label={shortLabelInserter}
              showTooltip
              aria-expanded={isInserterSidebarOpened}
            />
            <ToolbarItem
              ref={undoButton}
              as={Button}
              className="editor-history__undo"
              isPressed={false}
              onMouseDown={preventDefault}
              onClick={undoAction}
              disabled={!hasUndo}
              icon={undo}
              label={__('Undo', 'mailpoet')}
              showTooltip
            />
            <ToolbarItem
              ref={redoButton}
              as={Button}
              className="editor-history__redo"
              isPressed={false}
              onMouseDown={preventDefault}
              onClick={redoAction}
              disabled={!hasRedo}
              icon={redo}
              label={__('Redo', 'mailpoet')}
              showTooltip
            />
            <ToolbarItem
              ref={listviewButton}
              as={Button}
              className="editor-header-toolbar__document-overview-toggle edit-post-header-toolbar__document-overview-toggle"
              isPressed={isListviewSidebarOpened}
              onMouseDown={preventDefault}
              onClick={toggleListviewSidebar}
              disabled={false}
              icon={listView}
              label={__('List view', 'mailpoet')}
              showTooltip
              aria-expanded={isInserterSidebarOpened}
            />
          </div>
        </NavigableToolbar>
        {isFixedToolbarActive && isBlockSelected && (
          <>
            <div
              className={classnames('selected-block-tools-wrapper', {
                'is-collapsed': isBlockToolsCollapsed,
              })}
            >
              <BlockToolbar hideDragHandle />
            </div>
            <Button
              className="editor-header__block-tools-toggle edit-post-header__block-tools-toggle"
              icon={isBlockToolsCollapsed ? next : previous}
              onClick={() => {
                setIsBlockToolsCollapsed((collapsed) => !collapsed);
              }}
              label={
                isBlockToolsCollapsed
                  ? __('Show block tools', 'mailpoet')
                  : __('Hide block tools', 'mailpoet')
              }
            />
          </>
        )}
        {(!isFixedToolbarActive ||
          !isBlockSelected ||
          isBlockToolsCollapsed) && (
          <div className="editor-header__center edit-post-header__center">
            {hasDocumentNavigationHistory ? <DocumentBar /> : <CampaignName />}
          </div>
        )}
      </div>
      <div className="editor-header__settings edit-post-header__settings">
        <SaveButton />
        <PreviewDropdown />
        <SendButton />
        <PinnedItems.Slot scope={storeName} />
        <MoreMenu />
      </div>
    </div>
  );
}
