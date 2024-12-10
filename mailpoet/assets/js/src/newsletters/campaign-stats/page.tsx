import { useState, useEffect, useCallback } from 'react';
import { __, _x } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { RemoveWrapMargin } from 'common/remove-wrap-margin/remove-wrap-margin';
import { Tabs } from 'common/tabs/tabs';
import { Tab } from 'common/tabs/tab';
import { ErrorBoundary } from 'common';
import { NewsletterGeneralStats } from './newsletter-general-stats';
import { NewsletterType } from './newsletter-type';
import { NewsletterStatsInfo } from './newsletter-stats-info';
import { PremiumBanner } from './premium-banner';

type State = {
  item?: NewsletterType;
  loading: boolean;
};

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

  if (loading) return null;

  if (!newsletter) {
    return <h3> {__('This email does not exist.', 'mailpoet')} </h3>;
  }

  return (
    <>
      <HideScreenOptions />
      <RemoveWrapMargin />
      <TopBarWithBoundary />

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

        <Tabs activeKey="clicked">
          <Tab key="clicked" title={__('Clicked Links', 'mailpoet')}>
            {Hooks.applyFilters(
              'mailpoet_newsletters_clicked_links_table',
              <PremiumBanner />,
              newsletter.clicked_links,
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
        </Tabs>
      </div>
    </>
  );
}

CampaignStatsPage.displayName = 'CampaignStatsPage';
