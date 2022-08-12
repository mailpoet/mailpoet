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
      position="bottom center"
      renderToggle={({ isOpen, onToggle }) => (
        <PlainBodyTitle
          title={step.name && step.name.length > 0 ? step.name : stepType.title}
        >
          <TitleActionButton onClick={onToggle} aria-expanded={isOpen}>
            <Icon icon={edit} size={16} />
          </TitleActionButton>
        </PlainBodyTitle>
      )}
      renderContent={() => (
        <>
          <h3 className="mailpoet-step-name-title">Step name</h3>
          <TextControl
            className="mailpoet-step-name-input"
            placeholder={stepType.title}
            value={step.name}
            onChange={(value) => {
              dispatch(store).updateStepName(step.id, value);
            }}
          />
          <p className="mailpoet-step-name-description">
            Give the automation step a name that indicates its purpose. E.g
            “Abandoned cart recovery”. <br />
            This name will be displayed only to you and not to the clients.
          </p>
        </>
      )}
    />
  );
}
