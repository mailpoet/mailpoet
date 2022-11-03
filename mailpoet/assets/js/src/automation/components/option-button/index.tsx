import { Button, DropdownMenu } from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { StepMoreControlsType } from '../../types/filters';

type OptionButtonPropType = {
  variant: Button.ButtonVariant;
  controls: StepMoreControlsType;
  title: string;
  onClick: () => void;
};
export function OptionButton({
  controls,
  title,
  onClick,
  variant,
}: OptionButtonPropType): JSX.Element {
  const slots = Object.values(controls).filter((item) => item.slot);
  return (
    <div className="mailpoet-option-button">
      <Button
        variant={variant}
        className="mailpoet-option-button-main"
        onClick={onClick}
      >
        {title}
      </Button>
      {slots.length > 0 &&
        slots.map(({ key, slot }) => (
          <Fragment key={`slot-${key}`}>{slot}</Fragment>
        ))}
      {Object.values(controls).length > 0 && (
        <DropdownMenu
          className="mailpoet-option-button-opener"
          label={__('More', 'mailpoet')}
          icon={chevronDown}
          controls={Object.values(controls).map((item) => item.control)}
          popoverProps={{ position: 'bottom left' }}
        />
      )}
    </div>
  );
}
