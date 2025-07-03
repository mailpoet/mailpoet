import { PasswordInput } from 'common/index';
import { useAction, useSelector } from 'settings/store/hooks';

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
  return (
    <PasswordInput
      forceRevealed={forceRevealed}
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
    />
  );
}
