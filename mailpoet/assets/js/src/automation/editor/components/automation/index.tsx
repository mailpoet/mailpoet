import { useMemo } from 'react';
import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { AutomationCompositeContext, AutomationContext } from './context';
import { EmptyAutomation } from './empty-automation';
import { InserterPopover } from '../inserter-popover';
import { storeName } from '../../store';
import { Statistics } from './statistics';
import { Flow } from './flow';

type AutomationProps = {
  context: 'edit' | 'view';
};

export function Automation({ context }: AutomationProps): JSX.Element {
  const automationData = useSelect(
    (select) => select(storeName).getAutomationData(),
    [],
  );

  const automationContext = useMemo(() => ({ context }), [context]);

  const compositeState = useCompositeState({
    orientation: 'vertical',
    wrap: 'horizontal',
    shift: true,
  });

  if (!automationData) {
    return <EmptyAutomation />;
  }

  return (
    <AutomationContext.Provider value={automationContext}>
      <AutomationCompositeContext.Provider value={compositeState}>
        <Composite
          state={compositeState}
          role="tree"
          aria-label={__('Automation', 'mailpoet')}
          aria-orientation="vertical"
          className="mailpoet-automation-editor-automation"
        >
          <div className="mailpoet-automation-editor-automation-wrapper">
            <Statistics />
            <div className="mailpoet-automation-editor-automation-flow">
              <Flow stepData={automationData.steps.root} row={0} />
            </div>
            <div />
          </div>
          <InserterPopover />
        </Composite>
      </AutomationCompositeContext.Provider>
    </AutomationContext.Provider>
  );
}
