import { _x } from '@wordpress/i18n';
import { Button, Input } from 'common/index';
import { useAction, useSelector } from 'settings/store/hooks';
import { useState } from 'react';

type KeyInputPropType = {
  placeholder?: string;
  isFullWidth?: boolean;
  forceRevealed?: boolean;
};

export function KeyInput({
  placeholder,
  isFullWidth = false,
  forceRevealed = false,
}: KeyInputPropType) {
  const state = useSelector('getKeyActivationState')();
  const setState = useAction('updateKeyActivationState');
  const [isRevealed, setIsRevealed] = useState(false);
  const inputType = forceRevealed || isRevealed ? 'text' : 'password';
  const toggleButton = !forceRevealed && (
    <Button
      className="mailpoet-premium-key-toggle"
      variant="tertiary"
      onClick={() => setIsRevealed(!isRevealed)}
    >
      {isRevealed
        ? // translators: Used as a button to show or hide the premium key
          _x('Hide', 'verb', 'mailpoet')
        : // translators: Used as a button to show or hide the premium key
          _x('Show', 'verb', 'mailpoet')}
    </Button>
  );

  return (
    <Input
      type={inputType}
      id="mailpoet_premium_key"
      name="premium[premium_key]"
      placeholder={placeholder}
      isFullWidth={isFullWidth}
      value={state.key || ''}
      onChange={(event) =>
        setState({
          mssStatus: null,
          premiumStatus: null,
          premiumInstallationStatus: null,
          key: event.target.value.trim() || null,
        })
      }
      iconEnd={toggleButton}
    />
  );
}
