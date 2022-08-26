import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { Icon, plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { WorkflowCompositeContext } from './context';
import { Step } from './types';
import { storeName } from '../../store';

type Props = {
  step: Step;
};

export function AddTrigger({ step }: Props): JSX.Element {
  const compositeState = useContext(WorkflowCompositeContext);
  const { setInserterPopover } = useDispatch(storeName);

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-workflow-add-trigger"
      data-step-id={step.id}
      focusable
      onClick={(event) => {
        event.stopPropagation();
        setInserterPopover({
          anchor: (event.target as HTMLElement).closest('button'),
          type: 'triggers',
        });
      }}
    >
      <Icon icon={plus} size={16} />
      {__('Add trigger', 'mailpoet')}
    </CompositeItem>
  );
}
