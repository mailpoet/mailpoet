import { useEffect, useMemo, useState } from 'react';
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

  // We need to trigger one more render to center the scroll, because the sidebar
  // is using a React Portal, and it is not in the layout after the first render.
  const [rendered, setRendered] = useState(false);

  useEffect(() => {
    // first render
    if (!rendered) {
      setRendered(true);
      return;
    }

    // center the scroll to the first step
    const firstStep = document.querySelector('[data-step-id] > *');
    if (firstStep instanceof HTMLElement) {
      firstStep.scrollIntoView({ block: 'nearest', inline: 'center' });
    }
  }, [rendered]);

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
          <Statistics />
          <div className="mailpoet-automation-editor-automation-wrapper">
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
