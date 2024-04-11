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

// In older versions of Gutenberg (e.g. wp-6.4 and < 17.3) the fixed block toolbar is rendered automatically
// This is a workaround to hide the block toolbar in these versions
// We will remove this after we drop support for WP 6.4
const isInlinedBlockToolbarAvailable =
  window.MailPoetEmailEditor.bc_state.isInlinedBlockToolbarAvailable;

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
  } = useSelect(
    (select) => ({
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      isFixedToolbarActive: select(preferencesStore).get(
        'core',
        'fixedToolbar',
      ),
      isBlockSelected: !!select(blockEditorStore).getBlockSelectionStart(),
      hasUndo: select(coreDataStore).hasUndo(),
      hasRedo: select(coreDataStore).hasRedo(),
    }),
    [],
  );

  const preventDefault = (event) => {
    event.preventDefault();
  };

  const shortLabelInserter = !isInserterSidebarOpened ? __('Add') : __('Close');

  return (
    <div className="edit-post-header">
      <div className="edit-post-header__toolbar">
        <NavigableToolbar
          className="edit-post-header-toolbar is-unstyled editor-document-tools"
          aria-label={__('Email document tools', 'mailpoet')}
        >
          {/* edit-post-header-toolbar__left can be removed after we drop support of WP 6.4 */}
          <div className="edit-post-header-toolbar__left editor-document-tools__left">
            <ToolbarItem
              ref={inserterButton}
              as={Button}
              className="edit-post-header-toolbar__inserter-toggle"
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
              label={__('Undo')}
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
              label={__('Redo')}
              showTooltip
            />
            <ToolbarItem
              ref={listviewButton}
              as={Button}
              className="edit-post-header-toolbar__document-overview-toggle"
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
        {isInlinedBlockToolbarAvailable &&
          isFixedToolbarActive &&
          isBlockSelected && (
            <>
              <div
                className={classnames('selected-block-tools-wrapper', {
                  'is-collapsed': isBlockToolsCollapsed,
                })}
              >
                <BlockToolbar hideDragHandle />
              </div>
              <Button
                className="edit-post-header__block-tools-toggle"
                icon={isBlockToolsCollapsed ? next : previous}
                onClick={() => {
                  setIsBlockToolsCollapsed((collapsed) => !collapsed);
                }}
                label={
                  isBlockToolsCollapsed
                    ? __('Show block tools')
                    : __('Hide block tools')
                }
              />
            </>
          )}
        {(!isInlinedBlockToolbarAvailable ||
          !isFixedToolbarActive ||
          !isBlockSelected ||
          isBlockToolsCollapsed) && (
          <div className="edit-post-header__center">
            <CampaignName />
          </div>
        )}
      </div>
      <div className="edit-post-header__settings">
        <SaveButton />
        <PreviewDropdown />
        <SendButton />
        <PinnedItems.Slot scope={storeName} />
        <MoreMenu />
      </div>
    </div>
  );
}
