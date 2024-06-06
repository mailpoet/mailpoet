import { __ } from '@wordpress/i18n';
import { SenderDomainEntity } from './manage-sender-domain-types';
import { ErrorIcon, OkIcon, PendingIcon } from './icons';

type Status = 'valid' | 'invalid' | 'pending';

const getStatus = (data: SenderDomainEntity[]): Status => {
  if (
    data.some((item) => item.dns.some((record) => record.status === 'invalid'))
  ) {
    return 'invalid';
  }
  if (
    data.some((item) => item.dns.some((record) => record.status === 'pending'))
  ) {
    return 'pending';
  }
  return 'valid';
};

type Props = {
  data: SenderDomainEntity[];
};

export function DomainStatus({ data }: Props) {
  const status = getStatus(data);

  if (status === 'invalid') {
    return (
      <div className="mailpoet_manage_sender_domain_status mailpoet_manage_sender_domain_status_invalid">
        <ErrorIcon />
        {__('Error', 'mailpoet')}
      </div>
    );
  }

  if (status === 'pending') {
    return (
      <div className="mailpoet_manage_sender_domain_status mailpoet_manage_sender_domain_status_pending">
        <PendingIcon />
        {__('Pending authentication', 'mailpoet')}
      </div>
    );
  }

  return (
    <div className="mailpoet_manage_sender_domain_status mailpoet_manage_sender_domain_status_valid">
      <OkIcon />
      {__('Authenticated', 'mailpoet')}
    </div>
  );
}
