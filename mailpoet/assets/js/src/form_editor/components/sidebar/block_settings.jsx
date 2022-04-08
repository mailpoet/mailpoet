import { Panel } from '@wordpress/components';
import { BlockInspector } from '@wordpress/block-editor';

export default function BlockSettings() {
  return (
    <Panel>
      <BlockInspector />
    </Panel>
  );
}
