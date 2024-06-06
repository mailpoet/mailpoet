import { Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import { SenderDomainDnsItem } from './manage-sender-domain-types';
import { ErrorIcon } from './icons';

type DiffProps = {
  issues: SenderDomainDnsItem['issues'];
};

function Diff({ issues }: DiffProps) {
  let message = '';
  if (issues.needs_additions && issues.needs_deletions) {
    message = __(
      'Please add the text highlighted in green and remove the text highlighted in red:',
      'mailpoet',
    );
  } else if (issues.needs_additions) {
    message = __('Please add the text highlighted in green:', 'mailpoet');
  } else if (issues.needs_deletions) {
    message = __('Please remove the text highlighted in red:', 'mailpoet');
  }

  return (
    <>
      {message}{' '}
      {issues.has_errors && (
        // eslint-disable-next-line react/no-danger -- display pre-rendered diff
        <span dangerouslySetInnerHTML={{ __html: issues.diff }} />
      )}
    </>
  );
}

type SuffixProps = {
  dnsRecord: SenderDomainDnsItem;
};

function Suffix({ dnsRecord }: SuffixProps) {
  if (!dnsRecord.issues.has_multiple_values) {
    return null;
  }

  const message =
    dnsRecord.value === dnsRecord.current_value
      ? __('Please remove the duplicate value(s):', 'mailpoet')
      : `; ${__('and remove the duplicate value(s):', 'mailpoet')}`;

  return (
    <>
      {message}{' '}
      {dnsRecord.issues.duplicate_values?.map((value: string, i: number) => (
        // eslint-disable-next-line react/no-array-index-key -- we have no other key
        <Fragment key={i}>
          <del>{value}</del>
          {i === dnsRecord.issues.duplicate_values.length - 1 ? '' : ', '}
        </Fragment>
      ))}
    </>
  );
}

type Props = {
  dnsRecord: SenderDomainDnsItem;
};

export function DomainHostInfo({ dnsRecord }: Props) {
  if (dnsRecord.host_issues?.has_errors) {
    return (
      <div className="mailpoet_manage_sender_domain_item_error">
        <ErrorIcon />
        <div>
          {__('Incorrect value detected.', 'mailpoet')}{' '}
          <Diff issues={dnsRecord.host_issues} />
        </div>
      </div>
    );
  }

  return null;
}

export function DomainValueInfo({ dnsRecord }: Props) {
  if (dnsRecord.current_value === '') {
    return (
      <div className="mailpoet_manage_sender_domain_item_error">
        <ErrorIcon />
        <div>
          {__(
            'No records found. Please add this to your DNS settings.',
            'mailpoet',
          )}
        </div>
      </div>
    );
  }

  if (dnsRecord.status === 'invalid' && dnsRecord.issues?.has_errors) {
    const message = dnsRecord.issues?.has_multiple_values
      ? __('Multiple values detected.', 'mailpoet')
      : __('Incorrect value detected.', 'mailpoet');

    return (
      <div className="mailpoet_manage_sender_domain_item_error">
        <ErrorIcon />
        <div>
          {message} <Diff issues={dnsRecord.issues} />
          <Suffix dnsRecord={dnsRecord} />
        </div>
      </div>
    );
  }

  return null;
}
