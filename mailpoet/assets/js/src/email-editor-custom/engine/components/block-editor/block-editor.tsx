import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockInspector,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  BlockTools,
  BlockList,
  ObserveTyping,
  WritingFlow,
  __experimentalListView as ListView,
  __experimentalLibrary as Library,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  __experimentalUseResizeCanvas as useResizeCanvas,
} from '@wordpress/block-editor';
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import { storeName } from 'email-editor-custom/engine/store';

import { useEntityBlockEditor } from '@wordpress/core-data';
import { Sidebar } from '../sidebar/sidebar';

import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';

export function BlockEditor() {
  const { postId, initialSettings, previewDeviceType } = useSelect(
    (select) => ({
      postId: select(storeName).getEmailPostId(),
      initialSettings: select(storeName).getInitialEditorSettings(),
      previewDeviceType: select(storeName).getPreviewState().deviceType,
    }),
    [],
  );
  const [blocks, onInput, onChange] = useEntityBlockEditor(
    'postType',
    'mailpoet_email',
    { id: postId.toString() },
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
    <div className="edit-post-visual-editor">
      <div
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
          <BlockEditorProvider
            value={blocks}
            onInput={onInput}
            onChange={onChange}
            settings={initialSettings}
          >
            <Sidebar.InspectorFill>
              <BlockInspector />
            </Sidebar.InspectorFill>
            <ListviewSidebar.ListviewFill>
              <ListView />
            </ListviewSidebar.ListviewFill>
            <InserterSidebar.InserterFill>
              <Library
                showMostUsedBlocks
                showInserterHelpPanel={false}
                rootClientId={undefined}
                __experimentalInsertionIndex={undefined}
              />
            </InserterSidebar.InserterFill>
            <div className="editor-styles-wrapper">
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
            </div>
          </BlockEditorProvider>
        </div>
      </div>
    </div>
  );
}
