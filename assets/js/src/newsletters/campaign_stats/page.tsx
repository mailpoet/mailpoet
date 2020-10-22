import React, { useState, useEffect, useCallback } from 'react';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import { withRouter } from 'react-router-dom';
import InvalidMssKeyNotice from 'notices/invalid_mss_key_notice';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';

import { NewsletterGeneralStats } from './newsletter_general_stats';
import { NewsletterType } from './newsletter_type';
import { NewsletterStatsInfo } from './newsletter_stats_info';
import PremiumBanner from './premium_banner.jsx';
import Heading from '../../common/typography/heading/heading';

const hideWPScreenOptions = () => {
  const screenOptions = document.getElementById('screen-meta-links');
  if (screenOptions && screenOptions.style.display !== 'none') {
    screenOptions.style.display = 'none';
  }
};

const showWPScreenOptions = () => {
  const screenOptions = document.getElementById('screen-meta-links');
  if (screenOptions && screenOptions.style.display === 'none') {
    screenOptions.style.display = 'block';
  }
};

type Props = {
  match: {
    params: {
      id: string
    }
  }
  history: {
    push: (string) => void
  }
  location: object
};

type State = {
  item?: NewsletterType
  loading: boolean
}

const CampaignStatsPage = ({ match, history, location }: Props) => {
  const [state, setState] = useState<State>({
    item: undefined,
    loading: true,
  });

  const loadItem = useCallback((id) => {
    setState({ loading: true, item: state.item });
    MailPoet.Modal.loading(true);

    MailPoet.Ajax.post({
      api_version: (window as any).mailpoet_api_version,
      endpoint: (window as any).mailpoet_display_detailed_stats ? 'stats' : 'newsletters',
      action: (window as any).mailpoet_display_detailed_stats ? 'get' : 'getWithStats',
      data: {
        id,
      },
    }).always(() => {
      MailPoet.Modal.loading(false);
    }).done((response) => {
      setState({
        loading: false,
        item: response.data,
      });
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => error.message),
        { scroll: true }
      );
      setState({
        loading: false,
      });
      history.push('/');
    });
  }, [history, state.item]);

  useEffect(() => {
    // Scroll to top in case we're coming
    // from the middle of a long newsletter listing
    (window as any).scrollTo(0, 0);
    if (state.item?.id !== match.params.id) {
      loadItem(match.params.id);
    }
    hideWPScreenOptions();
    return () => {
      showWPScreenOptions();
    };
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

  return (
    <>
      <TopBarWithBeamer />
      <div className="mailpoet-stats-page">
        <InvalidMssKeyNotice
          mssKeyInvalid={(window as any).mailpoet_mss_key_invalid}
          subscribersCount={(window as any).mailpoet_subscribers_count}
        />

        <NewsletterStatsInfo newsletter={newsletter} />

        <NewsletterGeneralStats
          newsletter={newsletter}
          isWoocommerceActive={MailPoet.isWoocommerceActive}
        />

        <h2>{MailPoet.I18n.t('clickedLinks')}</h2>

        <div className="mailpoet_stat_triple-spaced">
          {Hooks.applyFilters('mailpoet_newsletters_clicked_links_table', <PremiumBanner />, newsletter.clicked_links)}
        </div>

        <div className="mailpoet_stat_triple-spaced">
          {Hooks.applyFilters('mailpoet_newsletters_purchased_products', null, newsletter)}
        </div>

        <h2>{MailPoet.I18n.t('subscriberEngagement')}</h2>
        <div className="mailpoet-stat-subscriber-engagement">
          {Hooks.applyFilters('mailpoet_newsletters_subscriber_engagement', <PremiumBanner />, location, match.params, newsletter)}
        </div>
      </div>
    </>
  );
};

export default withRouter(CampaignStatsPage);
