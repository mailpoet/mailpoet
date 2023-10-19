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
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';
import { useEntityBlockEditor } from '@wordpress/core-data';

import { storeName } from '../../store';
import { Sidebar } from '../sidebar/sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';

export function BlockEditor() {
  const {
    isFullscreenActive,
    isSidebarOpened,
    initialSettings,
    previewDeviceType,
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    postId,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(storeName).isFeatureActive('fullscreenMode'),
      isSidebarOpened: select(storeName).isSidebarOpened(),
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      postId: select(storeName).getEmailPostId(),
      initialSettings: select(storeName).getInitialEditorSettings(),
      previewDeviceType: select(storeName).getPreviewState().deviceType,
    }),
    [],
  );

  const className = classnames('interface-interface-skeleton', {
    'is-sidebar-opened': isSidebarOpened,
  });

  const [blocks, onInput, onChange] = useEntityBlockEditor(
    'postType',
    'mailpoet_email',
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore We have to use integer as we do in other places. Using string causes that id is referenced to a different post.
    { id: postId },
  );

  // These will be set by the user in the future in email or global styles.
  const layoutBackground = '#cccccc';
  const documentBackground = '#ffffff';

  let inlineStyles = useResizeCanvas(previewDeviceType);
  // UseResizeCanvas returns null if the previewDeviceType is Desktop.
  if (!inlineStyles) {
    inlineStyles = {
      height: '100%',
      width: '660px',
      margin: '0 auto',
      display: 'flex',
      flexFlow: 'column',
    };
  }
  inlineStyles.background = documentBackground;
  inlineStyles.transition = 'all 0.3s ease 0s';

  const contentAreaStyles = {
    background:
      previewDeviceType === 'Desktop' ? layoutBackground : 'transparent',
  };

  return (
    <BlockEditorProvider
      value={blocks}
      onInput={onInput}
      onChange={onChange}
      settings={initialSettings}
      useSubRegistry={false}
    >
      <FullscreenMode isActive={isFullscreenActive} />
      <Sidebar />
      <InterfaceSkeleton
        className={className}
        header={<Header />}
        content={
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
