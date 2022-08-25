import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { Icon, plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { WorkflowCompositeContext } from './context';
import { store } from '../../store';

export function AddTrigger(): JSX.Element {
  const compositeState = useContext(WorkflowCompositeContext);
  const { setInserterPopoverAnchor } = useDispatch(store);

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-workflow-add-trigger"
      focusable
      onClick={(event) => {
        event.stopPropagation();
        setInserterPopoverAnchor(
          (event.target as HTMLElement).closest('button'),
        );
      }}
    >
      <Icon icon={plus} size={16} />
      {__('Add trigger', 'mailpoet')}
    </CompositeItem>
  );
}
