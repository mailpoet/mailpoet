import { useRef } from '@wordpress/element';
import { PinnedItems } from '@wordpress/interface';
import { Button, ToolbarItem } from '@wordpress/components';
import { NavigableToolbar } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp, store as coreDataStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { plus, listView, undo, redo } from '@wordpress/icons';
import { MailPoetEmailData, storeName } from '../../store';
import { MoreMenu } from './more-menu';
import { PreviewDropdown } from '../preview';
import { SaveButton } from './save-button';

export function Header() {
  const inserterButton = useRef();
  const listviewButton = useRef();
  const undoButton = useRef();
  const redoButton = useRef();

  const { toggleInserterSidebar, toggleListviewSidebar } =
    useDispatch(storeName);
  const { undo: undoAction, redo: redoAction } = useDispatch(coreDataStore);
  const {
    postId,
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    hasUndo,
    hasRedo,
  } = useSelect(
    (select) => ({
      postId: select(storeName).getEmailPostId(),
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      hasUndo: select(coreDataStore).hasUndo(),
      hasRedo: select(coreDataStore).hasRedo(),
    }),
    [],
  );
  const [mailpoetEmailDa] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const mailpoetEmailData: MailPoetEmailData = mailpoetEmailDa;

  const preventDefault = (event) => {
    event.preventDefault();
  };

  const shortLabelInserter = !isInserterSidebarOpened ? __('Add') : __('Close');

  return (
    <div className="edit-post-header">
      <div className="edit-post-header__toolbar">
        <NavigableToolbar
          className="edit-post-header-toolbar"
          aria-label={__('Email document tools', 'mailpoet')}
        >
          <div className="edit-post-header-toolbar__left">
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
        <div className="edit-post-header__center">Todo Email Name</div>
      </div>
      <div className="edit-post-header__settings">
        <SaveButton postId={postId} />
        <PreviewDropdown />
        <Button
          variant="primary"
          onClick={() => {
            window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
          }}
        >
          {__('Send', 'mailpoet')}
        </Button>
        <PinnedItems.Slot scope={storeName} />
        <MoreMenu />
      </div>
    </div>
  );
}
