import React from 'react';
import { close } from '@wordpress/icons';
import {
  Button,
} from '@wordpress/components';
import {
  __experimentalLibrary as Library,
} from '@wordpress/block-editor';

type Props = {
  setIsInserterOpened: (boolean) => {}
}

const Inserter = ({
  setIsInserterOpened,
}: Props) => (
  <div className="edit-post-layout__inserter-panel">
    <div className="edit-post-layout__inserter-panel-header">
      <Button
        icon={close}
        onClick={() => setIsInserterOpened(false)}
      />
    </div>
    <div className="edit-post-layout__inserter-panel-content">
      <Library
        showMostUsedBlocks
        showInserterHelpPanel={false}
      />
    </div>
  </div>
);

export default Inserter;
