import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  BlockTools,
  BlockList,
  ObserveTyping,
  WritingFlow,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  __experimentalUseResizeCanvas as useResizeCanvas,
  BlockSelectionClearer,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  __unstableEditorStyles as EditorStyles,
} from '@wordpress/block-editor';
import { UnsavedChangesWarning } from '@wordpress/editor';
import { uploadMedia } from '@wordpress/media-utils';
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';

import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import { storeName } from '../../store';
import { AutosaveMonitor } from '../autosave';
import { BlockCompatibilityWarnings, Sidebar } from '../sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';
import { EditorNotices, EditorSnackbars, SentEmailNotice } from '../notices';
import { StylesSidebar } from '../styles-sidebar';

export function BlockEditor() {
  const {
    isFullscreenActive,
    isSidebarOpened,
    initialSettings,
    previewDeviceType,
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    isEmailLoaded,
    postId,
    canUserEditMedia,
    hasFixedToolbar,
    focusMode,
    styles,
    layout,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(storeName).isFeatureActive('fullscreenMode'),
      isSidebarOpened: select(storeName).isSidebarOpened(),
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      postId: select(storeName).getEmailPostId(),
      initialSettings: select(storeName).getInitialEditorSettings(),
      previewDeviceType: select(storeName).getPreviewState().deviceType,
      isEmailLoaded: select(storeName).isEmailLoaded(),
      canUserEditMedia: select(coreStore).canUser('create', 'media'),
      hasFixedToolbar: select(storeName).isFeatureActive('fixedToolbar'),
      focusMode: select(storeName).isFeatureActive('focusMode'),
      styles: select(storeName).getStyles(),
      layout: select(storeName).getLayout(),
    }),
    [],
  );

  const className = classnames('edit-post-layout', {
    'is-sidebar-opened': isSidebarOpened,
  });

  const [blocks, onInput, onChange] = useEntityBlockEditor(
    'postType',
    'mailpoet_email',
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore We have to use integer as we do in other places. Using string causes that id is referenced to a different post.
    { id: postId },
  );

  const layoutBackground = styles.color.background.layout;

  let inlineStyles = useResizeCanvas(previewDeviceType);
  // Adjust the inline styles for desktop preview. We want to set email width and center it.
  if (previewDeviceType === 'Desktop') {
    inlineStyles = {
      ...inlineStyles,
      height: 'auto',
      width: layout.contentSize,
      display: 'flex',
      flexFlow: 'column',
    };
  }

  inlineStyles.transition = 'all 0.3s ease 0s';
  // 72px top to place the email document nicely vertically in canvas. Same value is used for title in WP Post editor.
  // We use only 16px bottom to mitigate the issue with inserter popup displaying below the fold.
  inlineStyles.margin = '72px auto 16px auto';
  delete inlineStyles.marginLeft;
  delete inlineStyles.marginTop;
  delete inlineStyles.marginBottom;
  delete inlineStyles.marginRight;

  const contentAreaStyles = {
    background:
      previewDeviceType === 'Desktop' ? layoutBackground : 'transparent',
  };

  const settings = {
    ...initialSettings,
    mediaUpload: canUserEditMedia ? uploadMedia : null,
    hasFixedToolbar,
    focusMode,
  };

  // Do not render editor if email is not loaded yet.
  if (!isEmailLoaded) {
    return null;
  }

  return (
    <BlockEditorProvider
      value={blocks}
      onInput={onInput}
      onChange={onChange}
      settings={settings}
      useSubRegistry={false}
    >
      <FullscreenMode isActive={isFullscreenActive} />
      <UnsavedChangesWarning />
      <AutosaveMonitor />
      <SentEmailNotice />
      <Sidebar />
      <StylesSidebar />
      <InterfaceSkeleton
        className={className}
        header={<Header />}
        editorNotices={<EditorNotices />}
        notices={<EditorSnackbars />}
        content={
          <>
            <EditorNotices />
            <div className="edit-post-visual-editor">
              <BlockSelectionClearer
                className="edit-post-visual-editor__content-area"
                style={contentAreaStyles}
              >
                <div
                  style={inlineStyles}
                  className={classnames({
                    'is-mobile-preview': previewDeviceType === 'Mobile',
                    'is-desktop-preview': previewDeviceType === 'Desktop',
                  })}
                >
                  <EditorStyles
                    styles={settings.styles}
                    scope=".editor-styles-wrapper"
                  />
                  <BlockSelectionClearer
                    className="editor-styles-wrapper block-editor-writing-flow"
                    style={{ width: '100%' }}
                  >
                    {/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */}
                    {/* @ts-ignore BlockEditorKeyboardShortcuts.Register has no types */}
                    <BlockEditorKeyboardShortcuts.Register />
                    <BlockTools>
                      <WritingFlow>
                        <ObserveTyping>
                          <BlockList
                            className={classnames(
                              {
                                'is-mobile-preview':
                                  previewDeviceType === 'Mobile',
                              },
                              'is-layout-constrained has-global-padding',
                            )}
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore We have an older package of @wordpress/block-editor that doesn't contain the correct type
                            layout={layout}
                          />
                        </ObserveTyping>
                      </WritingFlow>
                    </BlockTools>
                  </BlockSelectionClearer>
                </div>
              </BlockSelectionClearer>
            </div>
          </>
        }
        sidebar={<ComplementaryArea.Slot scope={storeName} />}
        secondarySidebar={
          (isInserterSidebarOpened && <InserterSidebar />) ||
          (isListviewSidebarOpened && <ListviewSidebar />)
        }
      />
      {/* Rendering Warning component here ensures that the warning is displayed under the border configuration. */}
      <BlockCompatibilityWarnings />
    </BlockEditorProvider>
  );
}
