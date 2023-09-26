import { Panel } from '@wordpress/components';
import { BlockInspector } from '@wordpress/block-editor';

export function BlockSettings() {
  return (
    <Panel>
      <BlockInspector />
    </Panel>
  );
}
