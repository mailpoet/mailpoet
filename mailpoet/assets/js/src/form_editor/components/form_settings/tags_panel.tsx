import { Panel, PanelBody } from '@wordpress/components';

export function TagsPanel({ onToggle, isOpened }) {

  return (
    <Panel>
      <PanelBody
        title="Tags"
        opened={isOpened}
        onToggle={onToggle}
      >
      </PanelBody>
    </Panel>
  );
}
