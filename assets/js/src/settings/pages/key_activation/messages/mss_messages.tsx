import React from 'react';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks';

const ActiveMessage = () => (
  <div className="mailpoet_success mailpoet_mss_key_valid">
    {MailPoet.I18n.t('premiumTabMssActiveMessage')}
  </div>
);

type NotValidMessageProps = { message?: string }
const NotValidMessage = ({ message }: NotValidMessageProps) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabMssKeyNotValidMessage')}
  </div>
);

type MssNotActiveMessageProps = { activationCallback: () => any }
const MssNotActiveMessage = ({ activationCallback }: MssNotActiveMessageProps) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabMssNotActiveMessage')}
    {' '}
    <button type="button" className="button-link" onClick={activationCallback}>
      {MailPoet.I18n.t('premiumTabMssActivateMessage')}
    </button>
  </div>
);


type Props = {
  keyMessage?: string
  activationCallback: () => any,
}
export default function MssMessages(props: Props) {
  const mssKeyStatus = useSelector('getMssStatus')();
  switch (mssKeyStatus) {
    case 'valid_mss_active':
      return <ActiveMessage />;
    case 'valid_mss_not_active':
      return <MssNotActiveMessage activationCallback={props.activationCallback} />;
    case 'invalid':
      return <NotValidMessage message={props.keyMessage} />;
    default:
      return null;
  }
}
