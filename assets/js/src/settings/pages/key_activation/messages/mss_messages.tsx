import React from 'react';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks';
import { MssStatus } from 'settings/store/types';

const ActiveMessage = () => (
  <div className="mailpoet_success_item mailpoet_success mailpoet_mss_key_valid">
    {MailPoet.I18n.t('premiumTabMssActiveMessage')}
  </div>
);

type NotValidMessageProps = { message?: string }
const NotValidMessage = ({ message }: NotValidMessageProps) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabMssKeyNotValidMessage')}
  </div>
);
NotValidMessage.defaultProps = {
  message: '',
};

type MssNotActiveMessageProps = { activationCallback: () => any }
const MssNotActiveMessage = ({ activationCallback }: MssNotActiveMessageProps) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabMssNotActiveMessage')}
    {' '}
    <button type="button" className="mailpoet-button mailpoet-button-extra-small" onClick={activationCallback}>
      {MailPoet.I18n.t('premiumTabMssActivateMessage')}
    </button>
  </div>
);


type Props = {
  keyMessage?: string;
  activationCallback: () => any;
}
export default function MssMessages(props: Props) {
  const { mssStatus } = useSelector('getKeyActivationState')();
  switch (mssStatus) {
    case MssStatus.VALID_MSS_ACTIVE:
      return <ActiveMessage />;
    case MssStatus.VALID_MSS_NOT_ACTIVE:
      return <MssNotActiveMessage activationCallback={props.activationCallback} />;
    case MssStatus.INVALID:
      return <NotValidMessage message={props.keyMessage} />;
    default:
      return null;
  }
}
MssMessages.defaultProps = {
  keyMessage: '',
};
