import { useState, useEffect, useCallback } from 'react';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { withRouter } from 'react-router-dom';
import { InvalidMssKeyNotice } from 'notices/invalid_mss_key_notice';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { RemoveWrapMargin } from 'common/remove_wrap_margin/remove_wrap_margin';
import { Tabs } from 'common/tabs/tabs';
import { Tab } from 'common/tabs/tab';
import { Heading } from 'common/typography/heading/heading';

import { NewsletterGeneralStats } from './newsletter_general_stats';
import { NewsletterType } from './newsletter_type';
import { NewsletterStatsInfo } from './newsletter_stats_info';
import { PremiumBanner } from './premium_banner.jsx';

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

  if (newsletter?.subject && !newsletter?.queue) {
    return (
      <div>
        <Heading level={1}>{newsletter.subject}</Heading>
      </div>
    );
  }

  if (!newsletter) {
    return <h3> {MailPoet.I18n.t('emailDoesNotExist')} </h3>;
  }

  return (
    <>
      <HideScreenOptions />
      <RemoveWrapMargin />
      <TopBarWithBeamer />

      <div className="mailpoet-stats-page">
        <InvalidMssKeyNotice
          mssKeyInvalid={window.mailpoet_mss_key_invalid}
          subscribersCount={window.mailpoet_subscribers_count}
        />

        <NewsletterStatsInfo newsletter={newsletter} />

        <NewsletterGeneralStats
          newsletter={newsletter}
          isWoocommerceActive={MailPoet.isWoocommerceActive}
        />

        <Tabs activeKey="clicked">
          <Tab key="clicked" title={MailPoet.I18n.t('clickedLinks')}>
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

          <Tab key="engagement" title={MailPoet.I18n.t('subscriberEngagement')}>
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
            title={MailPoet.I18n.t('bounces')}
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

export const CampaignStatsPage = withRouter(CampaignStatsPageComponent);
