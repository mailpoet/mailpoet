import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { Icon, plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { AutomationContext, AutomationCompositeContext } from './context';
import { Step } from './types';
import { storeName } from '../../store';
import { sendTelemetryEvent } from '../../telemetry';

type Props = {
  step: Step;
  index: number;
};

export function AddTrigger({ step, index }: Props): JSX.Element {
  const { context } = useContext(AutomationContext);
  const compositeState = useContext(AutomationCompositeContext);
  const { setInserterPopover } = useDispatch(storeName);
  const { automationId } = useSelect(
    (s) => ({ automationId: s(storeName).getAutomationData().id }),
    [],
  );

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-add-trigger"
      data-previous-step-id={step.id}
      data-index={index}
      focusable
      onClick={
        context === 'edit'
          ? (event) => {
              event.stopPropagation();
              sendTelemetryEvent('button_click', {
                button_label: 'add_trigger',
                automation_id: automationId,
              });
              void setInserterPopover({
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
