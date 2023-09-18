import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { Icon, plus } from '@wordpress/icons';
import { AutomationCompositeContext } from './context';

type Props = {
  onClick?: (element: HTMLButtonElement) => void;
  previousStepId: string;
  index: number;
};

export function AddStepButton({
  onClick,
  previousStepId,
  index,
}: Props): JSX.Element {
  const compositeState = useContext(AutomationCompositeContext);
  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-editor-add-step-button"
      focusable
      data-previous-step-id={previousStepId}
      data-index={index}
      onClick={(event) => {
        event.stopPropagation();
        const button = (event.target as HTMLElement).closest('button');
        onClick(button);
      }}
    >
      <Icon icon={plus} size={16} />
    </CompositeItem>
  );
}
