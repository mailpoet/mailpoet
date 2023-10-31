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
} from '@wordpress/block-editor';
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
import { Sidebar } from '../sidebar/sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';
import { EditorNotices, EditorSnackbars } from '../notices';

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
    layoutStyles,
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
      layoutStyles: select(storeName).getLayoutStyles(),
    }),
    [],
  );

  const className = classnames(
    'interface-interface-skeleton',
    'edit-post-layout',
    {
      'is-sidebar-opened': isSidebarOpened,
    },
  );

  const [blocks, onInput, onChange] = useEntityBlockEditor(
    'postType',
    'mailpoet_email',
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore We have to use integer as we do in other places. Using string causes that id is referenced to a different post.
    { id: postId },
  );

  // These will be set by the user in the future in email or global styles.
  const layoutBackground = layoutStyles.background;
  const documentBackground = '#ffffff';

  let inlineStyles = useResizeCanvas(previewDeviceType);
  // UseResizeCanvas returns null if the previewDeviceType is Desktop.
  if (!inlineStyles) {
    inlineStyles = {
      height: 'auto',
      margin: '4rem auto', // 4em top/bottom to place the email document nicely vertically in canvas. Same value is used for title in WP Post editor.
      width: layoutStyles.width,
      display: 'flex',
      flexFlow: 'column',
    };
  }
  inlineStyles.background = documentBackground;
  inlineStyles.transition = 'all 0.3s ease 0s';
  inlineStyles['padding-bottom'] = layoutStyles.padding.bottom;
  inlineStyles['padding-left'] = layoutStyles.padding.left;
  inlineStyles['padding-right'] = layoutStyles.padding.right;
  inlineStyles['padding-top'] = layoutStyles.padding.top;

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
      <Sidebar />
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
                          <BlockList />
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
    </BlockEditorProvider>
  );
}
