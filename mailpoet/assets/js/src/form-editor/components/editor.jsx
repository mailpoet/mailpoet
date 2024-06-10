import classnames from 'classnames';
import '@wordpress/core-data';
import { useMemo } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { SlotFillProvider } from '@wordpress/components';
import { uploadMedia } from '@wordpress/media-utils';
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  InnerBlocks,
  BlockSelectionClearer,
  BlockTools,
  ObserveTyping,
  SETTINGS_DEFAULTS,
  WritingFlow,
} from '@wordpress/block-editor';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';

import { UnsavedChangesNotice } from 'common/notices/unsaved-changes-notice.jsx';
import { ErrorBoundary } from 'common';
import { fetchLinkSuggestions } from '../utils/link-suggestions';
import { Header } from './header.jsx';
import { Tutorial } from './tutorial';
import { Sidebar } from './sidebar/sidebar';
import { Inserter } from './inserter';
import { ListviewSidebar } from './list-view-sidebar';
import { Notices } from './notices.jsx';
import { FormStyles } from './form-styles.jsx';
import { FormPreview } from './preview/preview';
import { FormStylingBackground } from './form-styling-background.jsx';
import { CustomFontsStyleSheetLink } from './font-family-settings';
import { Fullscreen } from './fullscreen';
import { FONT_SIZES, storeName } from '../store';

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
  const {
    sidebarOpened,
    isInserterOpened,
    isListViewOpened,
    formBlocks,
    canUserUpload,
    selectedBlock,
  } = useSelect(
    (sel) => ({
      sidebarOpened: sel(storeName).getSidebarOpened(),
      isInserterOpened: sel(storeName).isInserterOpened(),
      isListViewOpened: sel(storeName).isListViewOpened(),
      formBlocks: sel(storeName).getFormBlocks(),
      canUserUpload: sel('core').canUser('create', 'media'),
      selectedBlock: sel('core/block-editor').getSelectedBlock(),
    }),
    [],
  );

  const layoutClass = classnames(
    'edit-post-layout interface-interface-skeleton',
    selectedBlock ? selectedBlock.name.replace('/', '-') : null,
    {
      'is-sidebar-opened': sidebarOpened,
    },
  );

  const { blocksChangedInBlockEditor, toggleInserter } = useDispatch(storeName);

  // Editor settings - see @wordpress/block-editor/src/store/defaults.js
  const editorSettings = useMemo(
    () => ({
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
        useRootPaddingAwareAlignments: true,
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
        typography: {
          defaultFontSizes: true,
          fontSizes: {
            default: FONT_SIZES,
          },
        },
      },
    }),
    [canUserUpload, toggleInserter],
  );

  return (
    <>
      <CustomFontsStyleSheetLink />
      <ShortcutProvider>
        <SlotFillProvider>
          <div className={layoutClass}>
            <div className="interface-interface-skeleton__editor">
              <div className="interface-interface-skeleton__header">
                <ErrorBoundary>
                  <Header
                    isInserterOpened={isInserterOpened}
                    setIsInserterOpened={toggleInserter}
                  />
                </ErrorBoundary>
              </div>
              <div className="interface-interface-skeleton__body">
                <BlockEditorProvider
                  value={formBlocks}
                  onChange={blocksChangedInBlockEditor}
                  settings={editorSettings}
                  useSubRegistry={false}
                >
                  {isInserterOpened && (
                    <div className="interface-interface-skeleton__secondary-sidebar">
                      <Inserter setIsInserterOpened={toggleInserter} />
                    </div>
                  )}
                  {isListViewOpened && (
                    <div className="interface-interface-skeleton__secondary-sidebar">
                      <ListviewSidebar />
                    </div>
                  )}
                  <div className="interface-interface-skeleton__content">
                    <ErrorBoundary>
                      <Notices />
                    </ErrorBoundary>
                    <UnsavedChangesNotice storeName="mailpoet-form-editor" />
                    <BlockSelectionClearer className="edit-post-visual-editor editor-styles-wrapper">
                      <BlockTools>
                        <BlockEditorKeyboardShortcuts />
                        <BlockEditorKeyboardShortcuts.Register />
                        <div className="mailpoet_form">
                          <WritingFlow>
                            <ObserveTyping>
                              <ErrorBoundary>
                                <FormStylingBackground>
                                  <BlockList
                                    renderAppender={
                                      selectedBlock
                                        ? false
                                        : InnerBlocks.ButtonBlockAppender
                                    }
                                  />
                                </FormStylingBackground>
                              </ErrorBoundary>
                            </ObserveTyping>
                          </WritingFlow>
                        </div>
                      </BlockTools>
                    </BlockSelectionClearer>
                  </div>
                  {sidebarOpened && (
                    <div className="interface-interface-skeleton__sidebar">
                      <ErrorBoundary>
                        <Sidebar />
                      </ErrorBoundary>
                    </div>
                  )}
                </BlockEditorProvider>
              </div>
              <ErrorBoundary>
                <FormStyles />
              </ErrorBoundary>
              <ErrorBoundary>
                <Fullscreen />
              </ErrorBoundary>
            </div>
          </div>
        </SlotFillProvider>
      </ShortcutProvider>
      <ErrorBoundary>
        <FormPreview />
      </ErrorBoundary>
      <Tutorial />
    </>
  );
}
