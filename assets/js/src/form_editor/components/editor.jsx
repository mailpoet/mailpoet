import React from 'react';
import { select, useSelect, useDispatch } from '@wordpress/data';
import { DropZoneProvider, Popover, SlotFillProvider } from '@wordpress/components';
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockSelectionClearer,
  WritingFlow,
  ObserveTyping,
} from '@wordpress/block-editor';
import classnames from 'classnames';
import Header from './header.jsx';
import Sidebar from './sidebar.jsx';
import FormTitle from './form_title.jsx';
import Notices from './notices.jsx';
import UnsavedChangesNotice from './unsaved_changes_notice.jsx';
import FormStyles from './form_styles.jsx';

// Editor settings - see @wordpress/block-editor/src/store/defaults.js
const editorSettings = {
  showInserterHelpPanel: false, // Disable TIPs section in add block pop up
};

/**
 * This component renders the form editor app.
 * Class names and organization of elements are done based on Gutenberg's edit-post package.
 * (https://github.com/WordPress/gutenberg/tree/master/packages/edit-post).
 * The goal is to render the same DOM for layout as Gutenberg post/page editor
 * so that we can reuse it's CSS.
 * To find out more about how block editor components are used visit
 * https://developer.wordpress.org/block-editor/packages/packages-block-editor/
 */
export default () => {
  const sidebarOpened = useSelect(
    (sel) => sel('mailpoet-form-editor').getSidebarOpened(),
    []
  );

  const layoutClass = classnames('edit-post-layout block-editor-editor-skeleton', {
    'is-sidebar-opened': sidebarOpened,
  });

  const { blocksChangedInBlockEditor } = useDispatch('mailpoet-form-editor');

  return (
    <DropZoneProvider>
      <SlotFillProvider>
        <div className={layoutClass}>
          <div className="block-editor-editor-skeleton__header">
            <Header />
          </div>
          <div className="block-editor-editor-skeleton__body">
            <BlockEditorProvider
              value={select('mailpoet-form-editor').getFormBlocks()}
              onInput={blocksChangedInBlockEditor}
              onChange={blocksChangedInBlockEditor}
              settings={editorSettings}
              useSubRegistry={false}
            >
              <div className="block-editor-editor-skeleton__content">
                <Notices />
                <Popover.Slot name="block-toolbar" />
                <UnsavedChangesNotice />
                <BlockSelectionClearer className="edit-post-visual-editor editor-styles-wrapper">
                  <BlockEditorKeyboardShortcuts />
                  <BlockEditorKeyboardShortcuts.Register />
                  <WritingFlow>
                    <ObserveTyping>
                      <FormTitle />
                      <BlockList />
                    </ObserveTyping>
                  </WritingFlow>
                </BlockSelectionClearer>
              </div>
              <div className="block-editor-editor-skeleton__sidebar">
                <div>
                  { sidebarOpened ? <Sidebar /> : null }
                </div>
              </div>
              <Popover.Slot />
            </BlockEditorProvider>
          </div>
          <FormStyles />
        </div>
      </SlotFillProvider>
    </DropZoneProvider>
  );
};
