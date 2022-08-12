import { Dropdown, TextControl } from '@wordpress/components';
import { edit, Icon } from '@wordpress/icons';
import { dispatch, useSelect } from '@wordpress/data';
import { PlainBodyTitle } from './plain-body-title';
import { TitleActionButton } from './title-action-button';
import { store } from '../../store';
import { Step } from '../workflow/types';

type Props = {
  step: Step;
};
export function StepName({ step }: Props): JSX.Element {
  const { stepType } = useSelect(
    (select) => ({
      stepType: select(store).getStepType(step.key),
    }),
    [],
  );
  return (
    <Dropdown
      className="mailpoet-step-name-dropdown"
      contentClassName="mailpoet-step-name-popover"
      position="bottom left"
      renderToggle={({ isOpen, onToggle }) => (
        <PlainBodyTitle
          title={step.name && step.name.length > 0 ? step.name : stepType.title}
        >
          <TitleActionButton
            onClick={onToggle}
            aria-expanded={isOpen}
            aria-label="Edit step name"
          >
            <Icon icon={edit} size={16} />
          </TitleActionButton>
        </PlainBodyTitle>
      )}
      renderContent={() => (
        <TextControl
          label="Step name"
          className="mailpoet-step-name-input"
          placeholder={stepType.title}
          value={step.name}
          onChange={(value) => {
            dispatch(store).updateStepName(step.id, value);
          }}
          help="Give the automation step a name that indicates its purpose. E.g
            “Abandoned cart recovery”. This name will be displayed only to you and not to the clients."
        />
      )}
    />
  );
}
