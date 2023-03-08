import ReactStringReplace from 'react-string-replace';
import { Button, Loader, TypographyHeading as Heading } from 'common';
import { __ } from '@wordpress/i18n';
import { Grid } from 'common/grid';
import { SenderDomainEntity } from './manage_sender_domain_types';
import { DomainKeyComponent } from './domain_key_component';
import { DomainStatusComponent } from './domain_status_component';

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
  if (rows.length === 0)
    return (
      <Grid.Column align="center">
        <Loader size={64} />
      </Grid.Column>
    );

  const { dns, domain } = rows[0];

  return (
    <div>
      <Heading level={2}> {__('Manage Sender Domain ', 'mailpoet')} </Heading>
      <p>
        {ReactStringReplace(
          __(
            'To help your audience and MailPoet authenticate you as the domain owner, please add the following DNS records to your domain’s DNS and click “Verify the DNS records”. Please note that it may take up to 24 hours for DNS changes to propagate after you make the change. [link]Read the guide[/link].',
            'mailpoet',
          ),
          /\[link](.*?)\[\/link]/g,
          (match) => (
            <a
              key={match}
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/188-how-to-set-up-mailpoet-sending-service#dns"
              target="_blank"
              rel="noopener noreferrer"
            >
              {match}
            </a>
          ),
        )}
      </p>

      <table className="mailpoet_manage_sender_domain widefat fixed striped">
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
            <th className="mailpoet_table_header">
              {' '}
              {__('Status', 'mailpoet')}{' '}
            </th>
          </tr>
        </thead>
        <tbody>
          {dns.map((dnsRecord, index) => (
            <tr key={`row_${domain}_${dnsRecord.host}`}>
              <td className="dns_record_type_column">{dnsRecord.type}</td>
              <td>
                <DomainKeyComponent
                  name={`dkim_host_${index}`}
                  value={dnsRecord.host}
                  readOnly
                  tooltip={__('Click here to copy', 'mailpoet')}
                />
              </td>
              <td>
                <DomainKeyComponent
                  name={`dkim_value_${index}`}
                  value={dnsRecord.value}
                  readOnly
                  tooltip={__('Click here to copy', 'mailpoet')}
                />
              </td>
              <td className="dns_record_type_column">
                <DomainStatusComponent
                  status={dnsRecord.status}
                  message={dnsRecord.message}
                  index={index}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <Button withSpinner={loadingButton} onClick={verifyDnsButtonClicked}>
        {__('Verify the DNS records', 'mailpoet')}
      </Button>
    </div>
  );
}

export { ManageSenderDomain };
