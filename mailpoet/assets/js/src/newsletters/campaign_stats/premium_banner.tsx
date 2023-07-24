import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { PremiumRequired } from 'common/premium_required/premium_required';
import { useState } from 'react';
import jQuery from 'jquery';
import ReactStringReplace from 'react-string-replace';
import { withBoundary } from '../../common';

function SkipDisplayingDetailedStats() {
  let ctaButton;
  let description;

  const [loading, setLoading] = useState(false);

  if (
    MailPoet.hasValidPremiumKey &&
    (!MailPoet.isPremiumPluginInstalled || !MailPoet.premiumActive)
  ) {
    description = (
      <p>
        {__(
          'Your current MailPoet plan includes advanced features, but they require the MailPoet Premium plugin to be installed and activated.',
          'mailpoet',
        )}
      </p>
    );
    ctaButton = (
      <Button
        withSpinner={loading}
        href={MailPoet.premiumPluginActivationUrl}
        rel="noopener noreferrer"
        onClick={(e) => {
          e.preventDefault();
          setLoading(true);

          jQuery
            .get(MailPoet.premiumPluginActivationUrl)
            .then((response) => {
              if (response.includes('Plugin activated')) {
                window.location.reload();
              }
            })
            .catch(() => {
              setLoading(false);
              MailPoet.Notice.error(
                ReactStringReplace(
                  __(
                    'We were unable to activate the premium plugin, please try visiting the [link]plugin page link[/link] to activate it manually.',
                    'mailpoet',
                  ),
                  /\[link\](.*?)\[\/link\]/g,
                  (match) =>
                    `<a rel="noreferrer" href=${MailPoet.adminPluginsUrl}>${match}</a>`,
                ).join(''),
                { isDismissible: false },
              );
            });
        }}
      >
        {loading
          ? __('Activating MailPoet premium...', 'mailpoet')
          : __('Activate MailPoet Premium plugin', 'mailpoet')}
      </Button>
    );
    // If the premium plugin is not installed, we need to provide a download link
    if (!MailPoet.isPremiumPluginInstalled) {
      ctaButton = (
        <Button
          href={MailPoet.premiumPluginDownloadUrl}
          target="_blank"
          rel="noopener noreferrer"
        >
          {__('Download MailPoet Premium plugin', 'mailpoet')}
        </Button>
      );
    }
  } else {
    description = (
      <p>
        {__(
          'Learn more about your subscribers and optimize your campaigns. See who opened your emails, which links they clicked, and then use the data to make your emails even better. And if you run a WooCommerce store, you’ll also see the revenue earned per email. All starting $10 per month.',
          'mailpoet',
        )}{' '}
        <a href="admin.php?page=mailpoet-upgrade">
          {__('Learn more', 'mailpoet')}
        </a>
        .
      </p>
    );
    ctaButton = (
      <Button
        href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          MailPoet.subscribersCount,
          MailPoet.currentWpUserEmail,
          'starter',
          { utm_medium: 'stats', utm_campaign: 'signup' },
        )}
        target="_blank"
        rel="noopener noreferrer"
      >
        {__('Upgrade', 'mailpoet')}
      </Button>
    );
  }

  return (
    <div className="mailpoet-stats-premium-required">
      <PremiumRequired
        title={__('This is a Premium feature', 'mailpoet')}
        message={description}
        actionButton={ctaButton}
      />
    </div>
  );
}

function PremiumBanner() {
  if (!window.mailpoet_display_detailed_stats) {
    return <SkipDisplayingDetailedStats />;
  }
  if (window.mailpoet_subscribers_limit_reached) {
    const hasValidApiKey = window.mailpoet_has_valid_api_key;
    const title = __('Upgrade required', 'mailpoet');
    const youReachedTheLimit = hasValidApiKey
      ? __(
          'Congratulations, you now have [subscribersCount] subscribers! Your plan is limited to [subscribersLimit] subscribers. You need to upgrade now to be able to continue using MailPoet.',
          'mailpoet',
        )
      : __(
          'Congratulations, you now have [subscribersCount] subscribers! Our free version is limited to [subscribersLimit] subscribers. You need to upgrade now to be able to continue using MailPoet.',
          'mailpoet',
        )
          .replace('[subscribersLimit]', MailPoet.subscribersLimit.toString())
          .replace('[subscribersCount]', MailPoet.subscribersCount.toString());
    const upgradeLink = hasValidApiKey
      ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl(MailPoet.pluginPartialKey)
      : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          window.mailpoet_subscribers_count + 1,
        );

    return (
      <div className="mailpoet-stats-premium-required">
        <PremiumRequired
          title={title}
          message={<p>{youReachedTheLimit}</p>}
          actionButton={
            <Button
              target="_blank"
              rel="noopener noreferrer"
              href={upgradeLink}
            >
              {__('Upgrade Now', 'mailpoet')}
            </Button>
          }
        />
      </div>
    );
  }
  return null;
}

PremiumBanner.displayName = 'PremiumBanner';

const PremiumBannerWithBoundary = withBoundary(PremiumBanner);
export { PremiumBannerWithBoundary as PremiumBanner };
