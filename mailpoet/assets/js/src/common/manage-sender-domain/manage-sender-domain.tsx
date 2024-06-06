import { Button, Loader, TypographyHeading as Heading } from 'common';
import { createInterpolateElement } from '@wordpress/element';
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
        {__(
          'Authenticate your sender domain to send emails from your email address. This helps your recipients verify you are the author of these emails and helps mailbox providers fight spam and improves your email delivery rates.',
          'mailpoet',
        )}
      </p>
      <p>
        {createInterpolateElement(
          __(
            'Please add the following DNS records to your domain’s DNS and click “Verify the DNS records”. Do note that it may take up to 24 hours for DNS changes to propagate after you make the change. <link>Read the guide</link>.',
            'mailpoet',
          ),
          {
            link: (
              // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
              <a
                className="mailpoet-link"
                href="https://kb.mailpoet.com/article/188-how-to-set-up-mailpoet-sending-service#dns"
                target="_blank"
                rel="noopener noreferrer"
              />
            ),
          },
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
      <div className="mailpoet_manage_sender_domain_actions">
        <Button withSpinner={loadingButton} onClick={verifyDnsButtonClicked}>
          {__('Verify the DNS records', 'mailpoet')}
        </Button>
      </div>
    </div>
  );
}

export { ManageSenderDomain };
