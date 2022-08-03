import classnames from 'classnames';
import '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { uploadMedia } from '@wordpress/media-utils';
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockSelectionClearer,
  BlockTools,
  WritingFlow,
  ObserveTyping,
  SETTINGS_DEFAULTS,
} from '@wordpress/block-editor';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';

import { fetchLinkSuggestions } from '../utils/link_suggestions';
import { Header } from './header.jsx';
import { Tutorial } from './tutorial';
import { Sidebar } from './sidebar/sidebar';
import { Inserter } from './inserter';
import { Notices } from './notices.jsx';
import { UnsavedChangesNotice } from './unsaved_changes_notice.jsx';
import { FormStyles } from './form_styles.jsx';
import { FormPreview } from './preview/preview';
import { FormStylingBackground } from './form_styling_background.jsx';
import { CustomFontsStyleSheetLink } from './font_family_settings';
import { Fullscreen } from './fullscreen';

/**
 * This component renders the form editor app.
 * Class names and organization of elements are done based on Gutenberg's edit-post package.
 * (https://github.com/WordPress/gutenberg/tree/master/packages/edit-post).
 * The goal is to render the same DOM for layout as Gutenberg post/page editor
 * so that we can reuse it's CSS.
 * To find out more about how block editor components are used visit
 * https://developer.wordpress.org/block-editor/packages/packages-block-editor/
 */
export function Editor() {
  const sidebarOpened = useSelect(
    (sel) => sel('mailpoet-form-editor').getSidebarOpened(),
    [],
  );

  const isInserterOpened = useSelect(
    (sel) => sel('mailpoet-form-editor').isInserterOpened(),
    [],
  );

  const formBlocks = useSelect(
    (sel) => sel('mailpoet-form-editor').getFormBlocks(),
    [],
  );

  const canUserUpload = useSelect(
    (sel) => sel('core').canUser('create', 'media'),
    [],
  );

  const selectedBlock = useSelect(
    (sel) => sel('core/block-editor').getSelectedBlock(),
    [],
  );

  const layoutClass = classnames(
    'edit-post-layout interface-interface-skeleton',
    selectedBlock ? selectedBlock.name.replace('/', '-') : null,
    {
      'is-sidebar-opened': sidebarOpened,
    },
  );

  const { blocksChangedInBlockEditor, toggleInserter } = useDispatch(
    'mailpoet-form-editor',
  );

  // Editor settings - see @wordpress/block-editor/src/store/defaults.js
  const editorSettings = {
    mediaUpload: canUserUpload ? uploadMedia : null,
    supportsLayout: false, // Disable layout settings for columns because the wide configuration doesn't work properly
    maxWidth: 580,
    enableCustomSpacing: true,
    enableCustomLineHeight: true,
    disableCustomFontSizes: false,
    enableCustomUnits: true,
    __experimentalFetchLinkSuggestions: fetchLinkSuggestions,
    __experimentalBlockPatterns: [], // we don't want patterns in our inserter
    __experimentalBlockPatternCategories: [],
    __experimentalSetIsInserterOpened: toggleInserter,
    __experimentalFeatures: {
      color: {
        custom: true,
        text: true,
        background: true,
        customGradient: true,
        defaultPalette: true,
        palette: {
          default: SETTINGS_DEFAULTS.colors,
        },
        gradients: {
          default: SETTINGS_DEFAULTS.gradients,
        },
      },
    },
  };

  return (
    <>
      <CustomFontsStyleSheetLink />
      <ShortcutProvider>
        <SlotFillProvider>
          <div className={layoutClass}>
            <div className="interface-interface-skeleton__editor">
              <div className="interface-interface-skeleton__header">
                <Header
                  isInserterOpened={isInserterOpened}
                  setIsInserterOpened={toggleInserter}
                />
              </div>
              <div className="interface-interface-skeleton__body">
                <BlockEditorProvider
                  value={formBlocks}
                  onInput={blocksChangedInBlockEditor}
                  onChange={blocksChangedInBlockEditor}
                  settings={editorSettings}
                  useSubRegistry={false}
                >
                  {isInserterOpened && (
                    <div className="interface-interface-skeleton__secondary-sidebar">
                      <Inserter setIsInserterOpened={toggleInserter} />
                    </div>
                  )}
                  <div className="interface-interface-skeleton__content">
                    <Notices />
                    <UnsavedChangesNotice />
                    <BlockSelectionClearer className="edit-post-visual-editor editor-styles-wrapper">
                      <BlockTools>
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
                      </BlockTools>
                    </BlockSelectionClearer>
                  </div>
                  {sidebarOpened && (
                    <div className="interface-interface-skeleton__sidebar">
                      <Sidebar />
                    </div>
                  )}
                </BlockEditorProvider>
              </div>
              <FormStyles />
              <Fullscreen />
            </div>
            <Popover.Slot />
          </div>
        </SlotFillProvider>
      </ShortcutProvider>
      <FormPreview />
      <Tutorial />
    </>
  );
}
