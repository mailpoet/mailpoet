import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { WorkflowCompositeContext } from './context';
import { PlusIcon } from '../icons';

type Props = {
  onClick?: (element: HTMLButtonElement) => void;
};

export function AddStepButton({ onClick }: Props): JSX.Element {
  const compositeState = useContext(WorkflowCompositeContext);
  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-editor-add-step-button"
      focusable
      onClick={(event) => {
        event.stopPropagation();
        const button = (event.target as HTMLElement).closest('button');
        onClick(button);
      }}
    >
      <PlusIcon />
    </CompositeItem>
  );
}
