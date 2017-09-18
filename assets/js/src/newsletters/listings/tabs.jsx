import React from 'react';
import { Link } from 'react-router';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const ListingTabs = React.createClass({
  getInitialState() {
    return {
      tab: null,
      tabs: [
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
      ],
    };
  },
  render() {
    const tabs = this.state.tabs.map((tab, index) => {
      const tabClasses = classNames(
        'nav-tab',
        { 'nav-tab-active': (this.props.tab === tab.name) }
      );

      return (
        <Link
          key={'tab-'+index}
          className={tabClasses}
          to={tab.link}
          onClick={() => MailPoet.trackEvent(`Tab Emails > ${tab.name} clicked`,
            { 'MailPoet Free version': window.mailpoet_version }
          )}
        >{ tab.label }</Link>
      );
    });

    return (
      <h2 className="nav-tab-wrapper">
        { tabs }
      </h2>
    );
  },
});

module.exports = ListingTabs;
