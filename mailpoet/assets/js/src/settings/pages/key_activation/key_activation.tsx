import { useAction, useSelector, useSetting } from 'settings/store/hooks';
import { t } from 'common/functions';
import { MssStatus } from 'settings/store/types';
import { Inputs, Label } from 'settings/components';
import { SetFromAddressModal } from 'common/set_from_address_modal';
import ReactStringReplace from 'react-string-replace';
import { KeyActivationButton } from 'common/premium_key/key_activation_button';
import { KeyInput } from 'common/premium_key/key_input';

type Props = {
  subscribersCount: number;
};

const premiumTabDescription = ReactStringReplace(
  t('premiumTabDescription'),
  /\[link\](.*?)\[\/link\]/g,
  (text) => (
    <a
      href="https://account.mailpoet.com/account?utm_source=plugin&utm_medium=settings&utm_campaign=activate-existing-plan&ref=settings-key-activation"
      target="_blank"
      rel="noopener noreferrer"
    >
      {text}
    </a>
  ),
);

const premiumTabGetKey = ReactStringReplace(
  t('premiumTabGetKey'),
  /\[link\](.*?)\[\/link\]/g,
  (text) => (
    <a
      href="https://account.mailpoet.com/account?utm_source=plugin&utm_medium=settings&utm_campaign=activate-existing-plan&ref=settings-key-activation"
      target="_blank"
      rel="noopener noreferrer"
    >
      {text}
    </a>
  ),
);

export function KeyActivation({ subscribersCount }: Props) {
  const state = useSelector('getKeyActivationState')();
  const setState = useAction('updateKeyActivationState');
  const sendCongratulatoryMssEmail = useAction('sendCongratulatoryMssEmail');
  const [senderAddress, setSenderAddress] = useSetting('sender', 'address');
  const [unauthorizedAddresses, setUnauthorizedAddresses] = useSetting(
    'authorized_emails_addresses_check',
  );
  const setSaveDone = useAction('setSaveDone');
  const setAuthorizedAddress = async (address: string) => {
    await setSenderAddress(address);
    await setUnauthorizedAddresses(null);
    setSaveDone();
  };

  const showFromAddressModal =
    state.fromAddressModalCanBeShown &&
    state.mssStatus === MssStatus.VALID_MSS_ACTIVE &&
    (!senderAddress || unauthorizedAddresses);

  return (
    <div className="mailpoet-settings-grid">
      <Label
        htmlFor="mailpoet_premium_key"
        title={t('premiumTabActivationKeyLabel')}
        description={
          <>
            {premiumTabDescription}
            <br />
            <br />
            {premiumTabGetKey}
            <br />
            <br />
            {ReactStringReplace(
              t('premiumTabGetPlan'),
              /\[link\](.*?)\[\/link\]/g,
              (text) => (
                <a
                  href={`https://account.mailpoet.com/?s=${subscribersCount}&utm_source=plugin&utm_medium=settings&utm_campaign=create-new-plan&ref=settings-key-activation`}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {text}
                </a>
              ),
            )}
          </>
        }
      />
      <Inputs>
        <KeyInput />
        <KeyActivationButton label={t('premiumTabVerifyButton')} />
      </Inputs>
      {showFromAddressModal && (
        <SetFromAddressModal
          onRequestClose={() => {
            setState({ fromAddressModalCanBeShown: false });
            sendCongratulatoryMssEmail();
          }}
          setAuthorizedAddress={setAuthorizedAddress}
        />
      )}
    </div>
  );
}
