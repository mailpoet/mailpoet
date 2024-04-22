import { FunctionComponent } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumBannerWithUpgrade } from 'common/premium-banner-with-upgrade/premium-banner-with-upgrade';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

export function NoAccessInfo(): JSX.Element {
  const getBannerMessage: FunctionComponent = () => {
    const message = __(
      'Learn more about how each of your subscribers is engaging with your emails. See which emails they’ve opened, the links they clicked. If you’re a WooCommerce store owner, you’ll also see any purchases made as a result of your emails. [link]Learn more[/link].',
      'mailpoet',
    );
    return (
      <p>
        {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
          <a
            key={match}
            href={MailPoet.premiumLink}
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ))}
      </p>
    );
  };
  const getCtaButton: FunctionComponent = () => (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
        MailPoet.subscribersCount,
        MailPoet.currentWpUserEmail,
        null,
        { utm_medium: 'stats', utm_campaign: 'signup' },
      )}
      target="_blank"
      rel="noopener noreferrer"
    >
      {__('Upgrade', 'mailpoet')}
    </Button>
  );

  return (
    <table
      className="mailpoet-listing-table"
      data-automation-id="subscriber-stats-no-access"
    >
      <thead>
        <tr>
          <th>{__('E-mail', 'mailpoet')}</th>
          <th>
            {
              // translators: Table column label for subscriber actions e.g. email open, link clicked
              __('Action', 'mailpoet')
            }
          </th>
          <th>
            {
              // translators: Table column label for count of subscriber actions
              __('Count', 'mailpoet')
            }
          </th>
          <th>
            {
              // translators: Table column label for date when subscriber action happened
              __('Action on', 'mailpoet')
            }
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colSpan={4}>
            <div className="mailpoet-subscriber-stats-no-access-content">
              <PremiumBannerWithUpgrade
                message={getBannerMessage({})}
                actionButton={getCtaButton({})}
                capabilities={{ detailedAnalytics: true }}
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  );
}
