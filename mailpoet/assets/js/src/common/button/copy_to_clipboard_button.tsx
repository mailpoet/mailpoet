import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Icon, copy, check, warning } from '@wordpress/icons';
import { Button } from './button';
import { copyToClipboard } from '../../utils';

const defaultButtonText = __('Copy to clipboard', 'mailpoet');
const defaultIcon = <Icon icon={copy} />;

type Props = {
  targetId: string;
  alwaysSelectText?: boolean;
} & React.ComponentProps<typeof Button>;

export function CopyToClipboardButton({
  targetId,
  alwaysSelectText = false,
  ...restProps
}: Props) {
  const [isDisabled, setIsDisabled] = useState(false);
  const [buttonText, setButtonText] = useState(defaultButtonText);
  const [iconStart, setIconStart] = useState(defaultIcon);

  const handleCopy = (wasSuccessful: boolean) => {
    if (wasSuccessful) {
      setIsDisabled(true);
      setButtonText(__('Copied to clipboard', 'mailpoet'));
      setIconStart(<Icon icon={check} />);

      setTimeout(() => {
        setIsDisabled(false);
        setButtonText(defaultButtonText);
        setIconStart(defaultIcon);
      }, 3000);
    } else {
      setIsDisabled(true);
      setButtonText(__('Could not copy to clipboard', 'mailpoet'));
      setIconStart(<Icon icon={warning} />);
    }
  };

  return (
    <Button
      {...restProps}
      isDisabled={isDisabled}
      iconStart={iconStart}
      onClick={async () => {
        await copyToClipboard(targetId, handleCopy, alwaysSelectText);
      }}
    >
      {buttonText}
    </Button>
  );
}
