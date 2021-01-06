import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';
import SubscribersInPlan from 'common/subscribers_in_plan';

const ListHeading = () => (
  <>
    <TopBarWithBeamer>
      <Link className="mailpoet-button mailpoet-button-small" to="/new">
        {plusIcon}
        <span>{MailPoet.I18n.t('new')}</span>
      </Link>
      <Link className="mailpoet-button mailpoet-button-small" to="/new-segment" data-automation-id="new-segment">
        {plusIcon}
        <span>{MailPoet.I18n.t('newSegment')}</span>
      </Link>
    </TopBarWithBeamer>

    <SubscribersInPlan
      subscribersInPlan={window.mailpoet_subscribers_in_plan_count}
      subscribersInPlanLimit={window.mailpoet_subscribers_limit}
    />
  </>
);

export default ListHeading;
