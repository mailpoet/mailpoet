import { Notice } from '@wordpress/components/build';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { MailPoet } from '../../../../../../../mailpoet';

function getUpgradeLink(): string {
  const utmArgs = {
    utm_source: 'plugin',
    utm_medium: 'upsell_modal',
    utm_campaign: 'automation-analytics',
  };
  const url = MailPoet.hasValidApiKey
    ? `https://account.mailpoet.com/orders/upgrade/${MailPoet.pluginPartialKey}`
    : `https://account.mailpoet.com/?s=${MailPoet.subscribersCount}&g=business&billing=monthly&email=${MailPoet.currentWpUserEmail}`;

  return addQueryArgs(url, utmArgs);
}

export function Upgrade({ text }: { text: string | JSX.Element }): JSX.Element {
  return (
    <Notice
      className="mailpoet-analytics-upgrade-banner"
      status="warning"
      isDismissible={false}
    >
      <span className="mailpoet-analytics-upgrade-banner__inner">
        {text}

        <Button href={getUpgradeLink()} isPrimary>
          {__('Upgrade', 'mailpoet')}
        </Button>
      </span>
    </Notice>
  );
}
