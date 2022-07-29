import ReactStringReplace from 'react-string-replace';
import { Button, Loader, TypographyHeading as Heading } from 'common';
import { MailPoet } from 'mailpoet';
import { SenderDomainEntity } from './manage_sender_domain_types';
import { DomainKeyComponent } from './domain_key_component';
import { DomainStatusComponent } from './domain_status_component';

type Props = {
  max_width: string;
  rows: Array<SenderDomainEntity>;
  loadingButton: boolean;
  verifyDnsButtonClicked: () => void;
};
function ManageSenderDomain({
  max_width,
  rows,
  loadingButton,
  verifyDnsButtonClicked,
}: Props) {
  if (rows.length === 0) return <Loader size={84} />;

  const { dns, domain } = rows[0];

  return (
    <div>
      <Heading level={2}>
        {' '}
        {MailPoet.I18n.t('manageSenderDomainHeaderTitle')}{' '}
      </Heading>
      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('manageSenderDomainHeaderSubtitle'),
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

      <table
        className="mailpoet_manage_sender_domain widefat fixed striped"
        style={{ maxWidth: max_width }}
      >
        <thead>
          <tr>
            <th className="mailpoet_table_header">
              {' '}
              {MailPoet.I18n.t('manageSenderDomainTableHeaderType')}{' '}
            </th>
            <th className="mailpoet_table_header">
              {' '}
              {MailPoet.I18n.t('manageSenderDomainTableHeaderHost')}{' '}
            </th>
            <th className="mailpoet_table_header">
              {' '}
              {MailPoet.I18n.t('manageSenderDomainTableHeaderValue')}{' '}
            </th>
            <th className="mailpoet_table_header">
              {' '}
              {MailPoet.I18n.t('manageSenderDomainTableHeaderStatus')}{' '}
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
                  tooltip={MailPoet.I18n.t('manageSenderDomainTooltipText')}
                />
              </td>
              <td>
                <DomainKeyComponent
                  name={`dkim_value_${index}`}
                  value={dnsRecord.value}
                  readOnly
                  tooltip={MailPoet.I18n.t('manageSenderDomainTooltipText')}
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
        {MailPoet.I18n.t('manageSenderDomainVerifyButton')}
      </Button>
    </div>
  );
}

export { ManageSenderDomain };
