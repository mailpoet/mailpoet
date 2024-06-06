import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Grid } from 'common/grid';
import { SenderDomainEntity } from './manage-sender-domain-types';
import { DomainKeyComponent } from './domain-key-component';
import { DomainHostInfo, DomainValueInfo } from './domain-key-info';

type Props = {
  rows: Array<SenderDomainEntity>;
  loadingButton: boolean;
  verifyDnsButtonClicked: () => void;
};

function ManageSenderDomain({
  rows,
  loadingButton,
  verifyDnsButtonClicked,
}: Props) {
  if (rows.length === 0) {
    return (
      <Grid.Column align="center">
        <Spinner className="mailpoet_manage_sender_domain_spinner" />
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
