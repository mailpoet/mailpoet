import { ComponentType, useContext, useEffect, useMemo, useState } from 'react';
import {
  __unstableComposite as Composite,
  __unstableCompositeItem as CompositeItem,
  __unstableUseCompositeState as useCompositeState,
  Button,
  Popover as WpPopover,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ErrorBoundary } from 'common';
import { Chip } from '../chip';
import { ColoredIcon } from '../icons';
import {
  StepErrors as StepErrorType,
  stepSidebarKey,
  storeName,
} from '../../store';

// properties "offset" and "placement" are missing in WpPopover type definition
const Popover: ComponentType<
  WpPopover.Props & {
    offset?: number;
    placement?: string;
  }
> = WpPopover;

export const ErrorsCompositeContext =
  createContext<ReturnType<typeof useCompositeState>>(undefined);

type StepErrorProps = {
  stepId: string;
};

function StepError({ stepId }: StepErrorProps): JSX.Element {
  const compositeState = useContext(ErrorsCompositeContext);

  const { steps, automationData } = useSelect(
    (select) => ({
      steps: select(storeName).getSteps(),
      automationData: select(storeName).getAutomationData(),
    }),
    [],
  );

  const { openSidebar, selectStep } = useDispatch(storeName);

  const stepData = automationData.steps[stepId];
  const step = steps.find(({ key }) => key === stepData.key);

  return (
    <CompositeItem
      className="mailpoet-automation-step-error"
      role="listitem"
      state={compositeState}
      onClick={() => {
        openSidebar(stepSidebarKey);
        selectStep(stepData);
      }}
    >
      <ColoredIcon
        icon={step.icon}
        foreground={step.foreground}
        background={step.background}
        width="23px"
        height="23px"
      />
      {step.title}
    </CompositeItem>
  );
}

StepError.displayName = 'StepError';

export function Errors(): JSX.Element | null {
  const [showPopover, setShowPopover] = useState(false);

  const compositeState = useCompositeState({
    orientation: 'vertical',
    shift: true,
  });

  const { errors, automationData } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
      automationData: select(storeName).getAutomationData(),
    }),
    [],
  );

  // walk the steps tree (breadth first) to produce stable error order
  const stepErrors = useMemo(() => {
    if (!errors) {
      return [];
    }

    const visited = new Map<string, StepErrorType | undefined>();
    const ids = automationData.steps.root.next_steps.map(({ id }) => id);
    while (ids.length > 0) {
      const id = ids.shift();
      if (!visited.has(id)) {
        visited.set(id, errors.steps[id]);
        automationData.steps[id]?.next_steps?.forEach((step) =>
          ids.push(step.id),
        );
      }
    }
    return [...visited.values()].filter((error) => !!error);
  }, [errors, automationData]);

  // automatically open the popover when errors appear
  const hasErrors = stepErrors.length > 0;
  useEffect(() => {
    if (hasErrors) {
      setShowPopover(true);
    }
  }, [hasErrors]);

  if (stepErrors.length === 0) {
    return null;
  }

  return (
    <div>
      <Button
        variant="link"
        onClick={() =>
          setShowPopover((prevState) =>
            prevState === undefined ? false : !prevState,
          )
        }
        onMouseDown={() =>
          // Catch and mark a mouse down event from an open popover with "undefined" to avoid closing it
          // (automatically via click outside) and reopening it right after (via the onClick handler).
          // The "onClose" method of the popover doesn't pass any events so we can't filter them.
          setShowPopover((prevState) => (prevState ? undefined : prevState))
        }
        style={{ textDecoration: 'none', borderRadius: 99999 }}
      >
        <Chip>{stepErrors.length} issues</Chip>
      </Button>
      {showPopover && (
        <Popover
          offset={10}
          placement="bottom-end"
          onClose={() =>
            setShowPopover((prevState) =>
              prevState === undefined ? undefined : false,
            )
          }
        >
          <ErrorsCompositeContext.Provider value={compositeState}>
            <Composite
              state={compositeState}
              role="list"
              aria-label={__('Automation errors', 'mailpoet')}
              className="mailpoet-automation-errors"
            >
              <div className="mailpoet-automation-errors-header">
                {
                  // translators: Label for a list of automation steps that are incomplete or have errors
                  __('The following steps are not fully set:', 'mailpoet')
                }
              </div>
              <ErrorBoundary>
                {stepErrors.map((error) => (
                  <StepError key={error.step_id} stepId={error.step_id} />
                ))}
              </ErrorBoundary>
            </Composite>
          </ErrorsCompositeContext.Provider>
        </Popover>
      )}
    </div>
  );
}
