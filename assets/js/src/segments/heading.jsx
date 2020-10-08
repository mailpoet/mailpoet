import React from 'react';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';
import SubscribersInPlan from 'common/subscribers_in_plan';

const ListHeading = ({ history }) => (
  <>
    <TopBarWithBeamer onLogoClick={() => history.push('/')}>
      <Link className="mailpoet-button" to="/new">
        {plusIcon}
        {MailPoet.I18n.t('new')}
      </Link>
      <Link className="mailpoet-button" to="/new-segment" data-automation-id="new-segment">
        {plusIcon}
        {MailPoet.I18n.t('newSegment')}
      </Link>
    </TopBarWithBeamer>
    <h1 className="mailpoet-newsletter-listing-heading-empty title">{' '}</h1>

    <SubscribersInPlan
      subscribersInPlan={window.mailpoet_subscribers_in_plan_count}
      subscribersInPlanLimit={window.mailpoet_subscribers_limit}
      mailpoetSubscribers={window.mailpoet_premium_subscribers_count}
      mailpoetSubscribersLimit={window.mailpoet_subscribers_limit}
      hasPremiumSupport={window.mailpoet_has_premium_support}
      wpUsersCount={window.mailpoet_wp_users_count}
      mssActive={window.mailpoet_mss_active}
    />
  </>
);

ListHeading.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(ListHeading);
