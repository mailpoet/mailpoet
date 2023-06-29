import { Tooltip } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Step } from '../../../../../../../../editor/components/automation/types';
import { EmailStats, OverviewSection, storeName } from '../../../../../store';
import { locale } from '../../../../../config';
import { formattedPrice } from '../../../../../formatter';
import { openTab } from '../../../../../navigation/open_tab';

type SendEmailPanelSectionProps = {
  label: string;
  value: string | JSX.Element;
  isLoading?: boolean;
};
function SendEmailPanelSection({
  label,
  value,
  isLoading,
}: SendEmailPanelSectionProps): JSX.Element {
  if (isLoading) {
    return (
      <div className="mailpoet-automation-analytics-send-email-panel-section is-loading" />
    );
  }
  return (
    <div className="mailpoet-automation-analytics-send-email-panel-section">
      <span className="mailpoet-automation-analytics-send-email-panel-label">
        {label}
      </span>
      <span className="mailpoet-automation-analytics-send-email-panel-value">
        {value}
      </span>
    </div>
  );
}

type SendEmailPanelProps = {
  step: Step;
};
export function SendEmailPanel({ step }: SendEmailPanelProps): JSX.Element {
  const { section } = useSelect(
    (s) =>
      ({
        section: s(storeName).getSection('overview'),
      } as {
        section: OverviewSection;
      }),
    [],
  );

  const isLoading = section.data === undefined;

  const email: EmailStats | undefined = !isLoading
    ? Object.values(section.data.emails).find(
        (item) => item.id === step.args.email_id,
      )
    : undefined;

  const sentLink =
    email === undefined ? (
      `0`
    ) : (
      <Tooltip text={__('View sending status', 'mailpoet')}>
        <a href={`?page=mailpoet-newsletters#/sending-status/${email.id}`}>
          {Intl.NumberFormat(locale.toString(), {
            notation: 'compact',
          }).format(email.sent.current)}
        </a>
      </Tooltip>
    );
  return (
    <div className="mailpoet-automation-analytics-send-email-panel">
      <SendEmailPanelSection
        label={__('Sent', 'mailpoet')}
        value={sentLink}
        isLoading={isLoading}
      />
      <SendEmailPanelSection
        label={__('Opened', 'mailpoet')}
        value={Intl.NumberFormat(locale.toString(), {
          notation: 'compact',
        }).format(email?.opened ?? 0)}
        isLoading={isLoading}
      />
      <SendEmailPanelSection
        label={__('Clicked', 'mailpoet')}
        value={Intl.NumberFormat(locale.toString(), {
          notation: 'compact',
        }).format(email?.clicked ?? 0)}
        isLoading={isLoading}
      />
      <hr />
      <SendEmailPanelSection
        label={__('Orders', 'mailpoet')}
        value={
          <Tooltip text={__('View orders', 'mailpoet')}>
            <a
              href={addQueryArgs(window.location.href, {
                tab: 'automation-orders',
              })}
              onClick={(e) => {
                e.preventDefault();
                openTab('orders');
              }}
            >
              {Intl.NumberFormat(locale.toString(), {
                notation: 'compact',
              }).format(email?.orders ?? 0)}
            </a>
          </Tooltip>
        }
        isLoading={isLoading}
      />
      <SendEmailPanelSection
        label={__('Revenue', 'mailpoet')}
        value={formattedPrice(email?.revenue ?? 0)}
        isLoading={isLoading}
      />
    </div>
  );
}
