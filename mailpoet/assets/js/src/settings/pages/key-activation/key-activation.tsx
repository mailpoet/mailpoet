import { useAction, useSelector, useSetting } from 'settings/store/hooks';
import { t } from 'common/functions';
import { MssStatus } from 'settings/store/types';
import { Inputs, Label } from 'settings/components';
import { SetFromAddressModal } from 'common/set-from-address-modal';
import ReactStringReplace from 'react-string-replace';
import { KeyActivationButton } from 'common/premium-key/key-activation-button';
import { KeyInput } from 'common/premium-key/key-input';

type Props = {
  subscribersCount: number;
};

const premiumTabDescription = ReactStringReplace(
  t('premiumTabDescription'),
  /\[link\](.*?)\[\/link\]/g,
  (text) => (
    <a
      key="premium-tab-description"
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
      key="premium-tab-get-key"
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
    void setSaveDone();
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
                  key="premium-tab-get-plan"
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
            void setState({ fromAddressModalCanBeShown: false });
            void sendCongratulatoryMssEmail();
          }}
          setAuthorizedAddress={setAuthorizedAddress}
        />
      )}
    </div>
  );
}
