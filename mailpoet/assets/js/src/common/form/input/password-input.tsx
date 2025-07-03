import { _x, __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

import { Button } from 'common/button/button';

import { Props as InputProps, Input } from './input';

type Props = InputProps & {
  forceRevealed?: boolean;
};

export function PasswordInput({ forceRevealed = false, ...attributes }: Props) {
  const [isRevealed, setIsRevealed] = useState(false);
  const inputType = forceRevealed || isRevealed ? 'text' : 'password';
  const toggleButton = !forceRevealed && (
    <Button
      className="mailpoet-password-input-toggle"
      variant="tertiary"
      aria-label={
        isRevealed
          ? __('Hide input value', 'mailpoet')
          : __('Show input value', 'mailpoet')
      }
      onClick={() => setIsRevealed(!isRevealed)}
    >
      {isRevealed
        ? // translators: Used as a button to show or hide the password
          _x('Hide', 'verb', 'mailpoet')
        : // translators: Used as a button to show or hide the password
          _x('Show', 'verb', 'mailpoet')}
    </Button>
  );

  return <Input type={inputType} iconEnd={toggleButton} {...attributes} />;
}
