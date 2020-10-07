import React, { useState } from 'react';
import '@wordpress/core-data';
import { select, useSelect, useDispatch } from '@wordpress/data';
import {
  DropZoneProvider,
  Popover,
  SlotFillProvider,
} from '@wordpress/components';
import { uploadMedia } from '@wordpress/media-utils';
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockSelectionClearer,
  WritingFlow,
  ObserveTyping,
} from '@wordpress/block-editor';
import classnames from 'classnames';
import { global as SETTINGS_DEFAULTS } from '../store/experimental-default-theme.json';
import fetchLinkSuggestions from '../utils/link_suggestions';
import Header from './header.jsx';
import Sidebar from './sidebar/sidebar';
import Inserter from './inserter';
import Notices from './notices.jsx';
import UnsavedChangesNotice from './unsaved_changes_notice.jsx';
import FormStyles from './form_styles.jsx';
import Preview from './preview/preview';
import FormStylingBackground from './form_styling_background.jsx';
import { CustomFontsStyleSheetLink } from './font_family_settings';

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
  const [isInserterOpen, setIsInserterOpen] = useState(false);
  const sidebarOpened = useSelect(
    (sel) => sel('mailpoet-form-editor').getSidebarOpened(),
    []
  );
  const canUserUpload = useSelect(
    (sel) => sel('core').canUser('create', 'media'),
    []
  );

  const selectedBlock = useSelect(
    (sel) => sel('core/block-editor').getSelectedBlock(),
    []
  );

  const layoutClass = classnames(
    'edit-post-layout interface-interface-skeleton',
    selectedBlock ? selectedBlock.name.replace('/', '-') : null,
    {
      'is-sidebar-opened': sidebarOpened,
    }
  );

  const { blocksChangedInBlockEditor } = useDispatch('mailpoet-form-editor');

  // Editor settings - see @wordpress/block-editor/src/store/defaults.js
  const editorSettings = {
    mediaUpload: canUserUpload ? uploadMedia : null,
    maxWidth: 580,
    __experimentalFetchLinkSuggestions: fetchLinkSuggestions,
    __experimentalBlockPatterns: [], // we don't want patterns in our inserter
    __experimentalBlockPatternCategories: [],
    __experimentalSetIsInserterOpened: setIsInserterOpen,
    __experimentalFeatures: {
      global: {
        color: {
          palette: SETTINGS_DEFAULTS.presets.color,
          gradients: SETTINGS_DEFAULTS.presets.gradient,
        },
        typography: {
          fontSizes: SETTINGS_DEFAULTS.presets['font-size'],
        },
      },
    },
  };

  return (
    <>
      <CustomFontsStyleSheetLink />
      <DropZoneProvider>
        <SlotFillProvider>
          <div className={layoutClass}>
            <div className="interface-interface-skeleton__header">
              <Header
                isInserterOpened={isInserterOpen}
                setIsInserterOpened={setIsInserterOpen}
              />
            </div>
            <div className="interface-interface-skeleton__body">
              <BlockEditorProvider
                value={select('mailpoet-form-editor').getFormBlocks()}
                onInput={blocksChangedInBlockEditor}
                onChange={blocksChangedInBlockEditor}
                settings={editorSettings}
                useSubRegistry={false}
              >
                <div className="interface-interface-skeleton__left-sidebar">
                  {isInserterOpen && (<Inserter setIsInserterOpened={setIsInserterOpen} />)}
                </div>
                <div className="interface-interface-skeleton__content">
                  <Notices />
                  <Popover.Slot name="block-toolbar" />
                  <UnsavedChangesNotice />
                  <BlockSelectionClearer className="edit-post-visual-editor editor-styles-wrapper">
                    <BlockEditorKeyboardShortcuts />
                    <BlockEditorKeyboardShortcuts.Register />
                    <div className="mailpoet_form">
                      <WritingFlow>
                        <ObserveTyping>
                          <FormStylingBackground>
                            <BlockList />
                          </FormStylingBackground>
                        </ObserveTyping>
                      </WritingFlow>
                    </div>
                  </BlockSelectionClearer>
                </div>
                <div className="interface-interface-skeleton__sidebar">
                  { sidebarOpened ? <Sidebar /> : null }
                </div>
              </BlockEditorProvider>
            </div>
            <FormStyles />
          </div>
          <Popover.Slot />
        </SlotFillProvider>
      </DropZoneProvider>
      <Preview />
    </>
  );
};
