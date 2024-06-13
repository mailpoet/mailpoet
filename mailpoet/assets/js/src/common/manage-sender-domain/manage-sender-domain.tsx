import { Button, Spinner } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Grid } from 'common/grid';
import { SenderDomainEntity } from './manage-sender-domain-types';
import { DomainKeyComponent } from './domain-key-component';
import { DomainHostInfo, DomainValueInfo } from './domain-key-info';
import { ErrorIcon } from './icons';

type Props = {
  rows: Array<SenderDomainEntity>;
  loadingButton: boolean;
  verifyDnsButtonClicked: () => void;
  error?: string;
};

function ManageSenderDomain({
  rows,
  loadingButton,
  verifyDnsButtonClicked,
  error,
}: Props) {
  if (rows.length === 0) {
    return (
      <Grid.Column align="center">
        {error ? (
          <strong className="mailpoet_error_item mailpoet_error">
            {' '}
            {error}{' '}
          </strong>
        ) : (
          <Spinner className="mailpoet_manage_sender_domain_spinner" />
        )}
      </Grid.Column>
    );
  }

  const { dns, domain } = rows[0];

  return (
    <div className="mailpoet_manage_sender_domain_wrapper">
      <div>
        {__(
          'Authenticate your sender domain to send emails from your email address. This helps your recipients verify you are the author of these emails and helps mailbox providers fight spam and improves your email delivery rates.',
          'mailpoet',
        )}
      </div>

      <ol>
        <li>
          <div className="mailpoet_manage_sender_domain_step_header">
            <strong>
              {__(
                'Please add the following DNS records to your domain’s DNS settings.',
              )}{' '}
            </strong>
            <a
              href="https://kb.mailpoet.com/article/295-spf-dkim-dmarc#authenticating"
              target="_blank"
              rel="noopener noreferrer"
            >
              {__('Read the guide', 'mailpoet')}
            </a>
          </div>

          {error && (
            <div className="mailpoet_manage_sender_domain_error">
              <ErrorIcon />
              <div>
                <strong>
                  {__('Error authenticating your sender domain.', 'mailpoet')}
                </strong>{' '}
                {createInterpolateElement(
                  __(
                    'We detected different records from your domain. Please fix them by following the error messages below and reauthenticate your domain. For more help, <link>read our guide</link>.',
                    'mailpoet',
                  ),
                  {
                    link: (
                      // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                      <a
                        href="https://kb.mailpoet.com/article/295-spf-dkim-dmarc#authenticating"
                        target="_blank"
                        rel="noopener noreferrer"
                      />
                    ),
                  },
                )}
              </div>
            </div>
          )}

          <table className="mailpoet_manage_sender_domain widefat striped">
            <thead>
              <tr>
                <th className="mailpoet_table_header">
                  {' '}
                  {__('Type', 'mailpoet')}{' '}
                </th>
                <th className="mailpoet_table_header">
                  {' '}
                  {__('Host', 'mailpoet')}{' '}
                </th>
                <th className="mailpoet_table_header">
                  {' '}
                  {__('Value', 'mailpoet')}{' '}
                </th>
              </tr>
            </thead>
            <tbody>
              {dns.map((dnsRecord, index) => (
                <tr key={`row_${domain}_${dnsRecord.host}`}>
                  <td className="dns_record_type_column">{dnsRecord.type}</td>
                  <td>
                    <DomainKeyComponent
                      name={`${dnsRecord.type}_host_${index}`}
                      value={dnsRecord.host}
                      readOnly
                      tooltip={__('Click here to copy', 'mailpoet')}
                    />
                    <DomainHostInfo dnsRecord={dnsRecord} />
                  </td>
                  <td>
                    <DomainKeyComponent
                      name={`${dnsRecord.type}_value_${index}`}
                      value={dnsRecord.value}
                      readOnly
                      tooltip={__('Click here to copy', 'mailpoet')}
                    />
                    <DomainValueInfo dnsRecord={dnsRecord} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </li>
        <li>
          <div className="mailpoet_manage_sender_domain_step_header">
            <strong>
              {__(
                'Once added, click the button below to authenticate your sender domain.',
                'mailpoet',
              )}
            </strong>{' '}
            {__(
              'MailPoet would verify your DNS records to ensure it matches. Do note that it may take up to 24 hours for DNS changes to propagate after you make the change.',
              'mailpoet',
            )}
          </div>
          <Button
            variant="primary"
            isBusy={loadingButton}
            onClick={verifyDnsButtonClicked}
          >
            {__('Verify the DNS records', 'mailpoet')}
          </Button>
        </li>
      </ol>
    </div>
  );
}

export { ManageSenderDomain };
