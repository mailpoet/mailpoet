import React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import PropTypes from 'prop-types';
import withNpsPoll from 'nps_poll.jsx';

class ListingTabs extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      tabs: Hooks.applyFilters('mailpoet_newsletters_listings_tabs', [
        {
          name: 'standard',
          label: MailPoet.I18n.t('tabStandardTitle'),
          link: '/standard',
        },
        {
          name: 'welcome',
          label: MailPoet.I18n.t('tabWelcomeTitle'),
          link: '/welcome',
        },
        {
          name: 'notification',
          label: MailPoet.I18n.t('tabNotificationTitle'),
          link: '/notification',
        },
      ]),
    };
  }

  render() {
    const tabs = this.state.tabs.map((tab) => {
      if (tab.display === false) {
        return null;
      }
      const tabClasses = classNames(
        'nav-tab',
        { 'nav-tab-active': (this.props.tab === tab.name) }
      );

      return (
        <Link
          key={`tab-${tab.label}`}
          className={tabClasses}
          data-automation-id={`tab-${tab.label}`}
          to={tab.link}
          onClick={() => MailPoet.trackEvent(`Tab Emails > ${tab.name} clicked`,
            { 'MailPoet Free version': window.mailpoet_version })}
        >
          { tab.label }
        </Link>
      );
    });

    return (
      <h2 className="nav-tab-wrapper" data-automation-id="newsletters_listing_tabs">
        { tabs }
      </h2>
    );
  }
}

ListingTabs.propTypes = {
  tab: PropTypes.string.isRequired,
};

export default withNpsPoll(ListingTabs);
