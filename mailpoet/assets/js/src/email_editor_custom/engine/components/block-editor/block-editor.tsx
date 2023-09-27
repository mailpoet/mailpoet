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
} from '@wordpress/block-editor';
import { useState } from '@wordpress/element';

import { Sidebar } from '../sidebar/sidebar';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';

export function BlockEditor() {
  const [documentBlocks, updateBlocks] = useState([]);

  // We can alter these to emulate different preview modes.
  const previewStyles = {
    height: '100%',
    width: '100%',
    margin: '0px',
    display: 'flex',
    flexFlow: 'column',
    background: 'white',
  };

  return (
    <div className="edit-post-visual-editor">
      <div className="edit-post-visual-editor__content-area">
        <div style={previewStyles}>
          <BlockEditorProvider
            value={documentBlocks}
            onInput={(blocks) => updateBlocks(blocks)}
            onChange={(blocks) => updateBlocks(blocks)}
            settings={{}}
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
