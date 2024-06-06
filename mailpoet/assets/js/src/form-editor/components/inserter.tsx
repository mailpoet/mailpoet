import { useSelect } from '@wordpress/data';
import { close } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { __experimentalLibrary as Library } from '@wordpress/block-editor';
import { storeName } from '../store';

type Props = {
  setIsInserterOpened: (boolean) => void;
};

export function Inserter({ setIsInserterOpened }: Props): JSX.Element {
  const insertPoint = useSelect(
    (sel) => sel(storeName).getInserterPanelInsertPoint(),
    [],
  );
  return (
    <div className="editor-inserter-sidebar">
      <div className="editor-inserter-sidebar__header">
        <Button icon={close} onClick={(): void => setIsInserterOpened(false)} />
      </div>
      <div className="editor-inserter-sidebar__content">
        <Library
          showMostUsedBlocks
          showInserterHelpPanel={false}
          rootClientId={insertPoint.rootClientId ?? undefined}
          __experimentalInsertionIndex={insertPoint.insertionIndex ?? undefined}
        />
      </div>
    </div>
  );
}
