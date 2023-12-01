import { useState, useEffect, useCallback } from 'react';
import { __, _x } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { withRouter } from 'react-router-dom';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { RemoveWrapMargin } from 'common/remove-wrap-margin/remove-wrap-margin';
import { Tabs } from 'common/tabs/tabs';
import { Tab } from 'common/tabs/tab';
import { ErrorBoundary } from 'common';
import { NewsletterGeneralStats } from './newsletter-general-stats';
import { NewsletterType } from './newsletter-type';
import { NewsletterStatsInfo } from './newsletter-stats-info';
import { PremiumBanner } from './premium-banner';

type Props = {
  match: {
    params: {
      id: string;
    };
  };
  history: {
    push: (string) => void;
  };
  // eslint-disable-next-line @typescript-eslint/ban-types -- we need to match `withRouter`
  location: object;
};

type State = {
  item?: NewsletterType;
  loading: boolean;
};

function CampaignStatsPageComponent({ match, history, location }: Props) {
  const [state, setState] = useState<State>({
    item: undefined,
    loading: true,
  });

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
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
          setState({
            loading: false,
          });
          history.push('/');
        });
    },
    [history, state.item],
  );

  useEffect(() => {
    // Scroll to top in case we're coming
    // from the middle of a long newsletter listing
    window.scrollTo(0, 0);
    if (state.item?.id !== match.params.id) {
      loadItem(match.params.id);
    }
  }, [match.params.id, loadItem, state.item]);

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
      <TopBarWithBeamer />

      <div className="mailpoet-stats-page">
        <MssAccessNotices />

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

          {Hooks.applyFilters(
            'mailpoet_newsletters_purchased_products',
            null,
            newsletter,
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
              match.params,
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
              match.params,
            )}
          </Tab>
        </Tabs>
      </div>
    </>
  );
}

CampaignStatsPageComponent.displayName = 'CampaignStatsPage';
export const CampaignStatsPage = withRouter(CampaignStatsPageComponent);
