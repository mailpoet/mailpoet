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

  const { steps, workflowData } = useSelect(
    (select) => ({
      steps: select(storeName).getSteps(),
      workflowData: select(storeName).getWorkflowData(),
    }),
    [],
  );

  const { openSidebar, selectStep } = useDispatch(storeName);

  const stepData = workflowData.steps[stepId];
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

export function Errors(): JSX.Element | null {
  const [showPopover, setShowPopover] = useState(false);

  const compositeState = useCompositeState({
    orientation: 'vertical',
    shift: true,
  });

  const { errors, workflowData } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
      workflowData: select(storeName).getWorkflowData(),
    }),
    [],
  );

  // walk the steps tree (breadth first) to produce stable error order
  const stepErrors = useMemo(() => {
    if (!errors) {
      return [];
    }

    const visited = new Map<string, StepErrorType | undefined>();
    const ids = workflowData.steps.root.next_steps.map(({ id }) => id);
    while (ids.length > 0) {
      const id = ids.shift();
      if (!visited.has(id)) {
        visited.set(id, errors.steps[id]);
        workflowData.steps[id]?.next_steps?.forEach((step) =>
          ids.push(step.id),
        );
      }
    }
    return [...visited.values()].filter((error) => !!error);
  }, [errors, workflowData]);

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
              aria-label={__('Workflow errors', 'mailpoet')}
              className="mailpoet-automation-errors"
            >
              <div className="mailpoet-automation-errors-header">
                {
                  // translators: Label for a list of automation workflow steps that are incomplete or have errors
                  __('The following steps are not fully set:', 'mailpoet')
                }
              </div>
              {stepErrors.map((error) => (
                <StepError key={error.step_id} stepId={error.step_id} />
              ))}
            </Composite>
          </ErrorsCompositeContext.Provider>
        </Popover>
      )}
    </div>
  );
}
