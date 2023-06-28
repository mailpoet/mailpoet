import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { Icon, plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { AutomationCompositeContext } from './context';
import { Step } from './types';
import { storeName } from '../../store';

type Props = {
  step: Step;
  context: 'edit' | 'view';
};

export function AddTrigger({ step, context }: Props): JSX.Element {
  const compositeState = useContext(AutomationCompositeContext);
  const { setInserterPopover } = useDispatch(storeName);

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-add-trigger"
      data-previous-step-id={step.id}
      focusable
      onClick={
        context === 'edit'
          ? (event) => {
              event.stopPropagation();
              setInserterPopover({
                anchor: (event.target as HTMLElement).closest('button'),
                type: 'triggers',
              });
            }
          : undefined
      }
    >
      <Icon icon={plus} size={16} />
      {__('Add trigger', 'mailpoet')}
    </CompositeItem>
  );
}
