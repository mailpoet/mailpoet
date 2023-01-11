import { Dispatch, SetStateAction, useState } from 'react';
import { Button, DropdownMenu } from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { StepMoreControlsType } from '../../types/filters';

type OptionButtonPropType = {
  variant: Button.ButtonVariant;
  controls: StepMoreControlsType;
  title: string;
  onClick: (setIsBusy: Dispatch<SetStateAction<boolean>>) => void;
};
export function OptionButton({
  controls,
  title,
  onClick,
  variant,
}: OptionButtonPropType): JSX.Element {
  const [isBusy, setIsBusy] = useState(false);
  const slots = Object.values(controls).filter((item) => item.slot);
  const dropDownMenuClassNames = isBusy
    ? `mailpoet-option-button-opener is-busy`
    : `mailpoet-option-button-opener`;
  return (
    <div className="mailpoet-option-button">
      <Button
        isBusy={isBusy}
        disabled={isBusy}
        variant={variant}
        className="mailpoet-option-button-main"
        onClick={() => {
          setIsBusy(true);
          onClick(setIsBusy);
        }}
      >
        {title}
      </Button>
      {slots.length > 0 &&
        slots.map(({ key, slot }) => (
          <Fragment key={`slot-${key}`}>{slot}</Fragment>
        ))}
      {Object.values(controls).length > 0 && (
        <DropdownMenu
          className={dropDownMenuClassNames}
          label={__('More', 'mailpoet')}
          icon={chevronDown}
          controls={Object.values(controls).map((item) => {
            const control = {
              ...item.control,
              onClick: () => {
                setIsBusy(true);
                item.control.onClick(setIsBusy);
              },
            };

            return control;
          })}
          popoverProps={{ position: 'bottom left' }}
        />
      )}
    </div>
  );
}
