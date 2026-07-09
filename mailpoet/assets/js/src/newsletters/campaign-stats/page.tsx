import { useState, useEffect, useCallback } from 'react';
import { __, _x } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { RemoveWrapMargin } from 'common/remove-wrap-margin/remove-wrap-margin';
import { Tabs } from 'common/tabs/tabs';
import { Tab } from 'common/tabs/tab';
import { ErrorBoundary } from 'common';
import { isTimezoneCampaignQueue } from 'newsletters/timezone-campaign';
import { NewsletterGeneralStats } from './newsletter-general-stats';
import { NewsletterType } from './newsletter-type';
import { NewsletterStatsInfo } from './newsletter-stats-info';
import { PremiumBanner } from './premium-banner';
import { TimezoneSchedule } from './timezone-schedule';

type StatsTabKey =
  | 'clicked'
  | 'products'
  | 'engagement'
  | 'bounces'
  | 'unsubscribe-reasons'
  | 'timezones';

type State = {
  item?: NewsletterType;
  loading: boolean;
};

const statsTabKeys: StatsTabKey[] = [
  'clicked',
  'products',
  'engagement',
  'bounces',
  'unsubscribe-reasons',
  'timezones',
];

const legacyListingParamKeys = [
  'group',
  'filter',
  'search',
  'page',
  'sort_by',
  'sort_order',
];

function getActiveStatsTab(path?: string): StatsTabKey {
  const firstPart = (path || '').split('/').filter(Boolean)[0];
  if (statsTabKeys.includes(firstPart as StatsTabKey)) {
    return firstPart as StatsTabKey;
  }
  if (legacyListingParamKeys.some((key) => firstPart?.startsWith(`${key}[`))) {
    return 'engagement';
  }
  return 'clicked';
}

function getStatsTabUrl(newsletterId: string, tabKey: string): string {
  if (tabKey === 'clicked') {
    return `/stats/${newsletterId}`;
  }
  return `/stats/${newsletterId}/${tabKey}`;
}

export function CampaignStatsPage() {
  const [state, setState] = useState<State>({
    item: undefined,
    loading: true,
  });

  const location = useLocation();
  const navigate = useNavigate();
  const params = useParams();

  const loadItem = useCallback(
    (id) => {
      setState({ loading: true, item: state.item });
      MailPoet.Modal.loading(true);

      void MailPoet.Ajax.post({
        api_version: MailPoet.apiVersion,
        endpoint: window.mailpoet_display_detailed_stats
          ? 'stats'
          : 'newsletters',
        action: window.mailpoet_display_detailed_stats ? 'get' : 'getWithStats',
        data: {
          id,
          accept: 'all',
        },
      })
        .always(() => {
          MailPoet.Modal.loading(false);
        })
        .done((response) => {
          setState({
            loading: false,
            item: response.data,
          });
        })
        .fail((response: ErrorResponse) => {
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
          setState({
            loading: false,
          });
          navigate('/');
        });
    },
    [navigate, state.item],
  );

  useEffect(() => {
    // Scroll to top in case we're coming
    // from the middle of a long newsletter listing
    window.scrollTo(0, 0);
    if (state.item?.id !== params.id) {
      loadItem(params.id);
    }
  }, [params.id, loadItem, state.item]);

  const { item, loading } = state;
  const newsletter = item;
  const requestedTab = getActiveStatsTab(params['*']);
  let activeTab = requestedTab;

  useEffect(() => {
    if (loading || !newsletter) {
      return;
    }

    if (
      (requestedTab === 'products' && !MailPoet.isWoocommerceActive) ||
      (requestedTab === 'timezones' &&
        !isTimezoneCampaignQueue(newsletter.queue))
    ) {
      navigate(getStatsTabUrl(newsletter.id, 'clicked'), { replace: true });
    }
  }, [loading, navigate, newsletter, requestedTab]);

  if (loading) return null;

  if (!newsletter) {
    return <h3> {__('This email does not exist.', 'mailpoet')} </h3>;
  }

  if (activeTab === 'products' && !MailPoet.isWoocommerceActive) {
    activeTab = 'clicked';
  }
  if (activeTab === 'timezones' && !isTimezoneCampaignQueue(newsletter.queue)) {
    activeTab = 'clicked';
  }

  return (
    <>
      <RemoveWrapMargin />
      <TopBarWithBoundary hideScreenOptions />

      <div className="mailpoet-stats-page">
        <ErrorBoundary>
          <NewsletterStatsInfo newsletter={newsletter} />
        </ErrorBoundary>

        <ErrorBoundary>
          <NewsletterGeneralStats
            newsletter={newsletter}
            isWoocommerceActive={MailPoet.isWoocommerceActive}
          />
        </ErrorBoundary>

        <Tabs
          activeKey={activeTab}
          onSwitch={(tabKey) => {
            navigate(getStatsTabUrl(newsletter.id, tabKey));
          }}
        >
          <Tab key="clicked" title={__('Clicked Links', 'mailpoet')}>
            {Hooks.applyFilters(
              'mailpoet_newsletters_clicked_links_table',
              <PremiumBanner />,
              newsletter.clicked_links,
              newsletter,
            )}
          </Tab>

          {MailPoet.isWoocommerceActive && (
            <Tab
              key="products"
              title={__('Products Sold', 'mailpoet')}
              automationId="products-sold-tab"
            >
              {Hooks.applyFilters(
                'mailpoet_newsletters_purchased_products',
                <PremiumBanner />,
                newsletter,
              )}
            </Tab>
          )}

          <Tab
            key="engagement"
            title={__('Subscriber Engagement', 'mailpoet')}
            automationId="engagement-tab"
          >
            {Hooks.applyFilters(
              'mailpoet_newsletters_subscriber_engagement',
              <PremiumBanner />,
              location,
              params,
              newsletter,
            )}
          </Tab>

          <Tab
            key="bounces"
            title={_x(
              'Bounces',
              'A tab title for the list of bounces (w.wiki/45Qc)',
              'mailpoet',
            )}
            automationId="bounces-tab"
          >
            {Hooks.applyFilters(
              'mailpoet_newsletters_bounces',
              <PremiumBanner />,
              location,
              params,
            )}
          </Tab>

          <Tab
            key="unsubscribe-reasons"
            title={__('Unsubscribe reasons', 'mailpoet')}
            automationId="unsubscribe-reasons-tab"
          >
            {Hooks.applyFilters(
              'mailpoet_newsletters_unsubscribe_reasons',
              <PremiumBanner />,
              newsletter.statistics.unsubscribeReasons,
              newsletter,
            )}
          </Tab>

          {isTimezoneCampaignQueue(newsletter.queue) && (
            <Tab
              key="timezones"
              title={__('Time zones', 'mailpoet')}
              automationId="timezones-tab"
            >
              <ErrorBoundary>
                <TimezoneSchedule newsletter={newsletter} />
              </ErrorBoundary>
            </Tab>
          )}
        </Tabs>
      </div>
    </>
  );
}

CampaignStatsPage.displayName = 'CampaignStatsPage';
