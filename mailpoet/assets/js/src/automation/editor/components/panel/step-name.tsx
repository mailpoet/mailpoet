import { Dropdown, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { edit, Icon } from '@wordpress/icons';
import { PlainBodyTitle } from './plain-body-title';
import { TitleActionButton } from './title-action-button';

type Props = {
  currentName: string;
  defaultName: string;
  update: (value: string) => void;
};
export function StepName({
  currentName,
  defaultName,
  update,
}: Props): JSX.Element {
  return (
    <Dropdown
      className="mailpoet-step-name-dropdown"
      contentClassName="mailpoet-step-name-popover"
      position="bottom left"
      renderToggle={({ isOpen, onToggle }) => (
        <PlainBodyTitle
          title={currentName.length > 0 ? currentName : defaultName}
        >
          <TitleActionButton
            onClick={onToggle}
            aria-expanded={isOpen}
            aria-label={__('Edit step name', 'mailpoet')}
          >
            <Icon icon={edit} size={16} />
          </TitleActionButton>
        </PlainBodyTitle>
      )}
      renderContent={() => (
        <TextControl
          label={__('Step name', 'mailpoet')}
          className="mailpoet-step-name-input"
          placeholder={defaultName}
          value={currentName}
          onChange={update}
          help={__(
            'Give the automation step a name that indicates its purpose. E.g "Abandoned cart recovery". This name will be displayed only to you and not to the clients.',
            'mailpoet',
          )}
        />
      )}
    />
  );
}
