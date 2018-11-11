import PropTypes from 'prop-types';
import React from 'react';
import { Link } from 'react-router';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const tabs = [
  {
    name: 'knowledgeBase',
    label: MailPoet.I18n.t('tabKnowledgeBaseTitle'),
    link: '/knowledgeBase',
  },
  {
    name: 'systemStatus',
    label: MailPoet.I18n.t('tabSystemStatusTitle'),
    link: '/systemStatus',
  },
  {
    name: 'systemInfo',
    label: MailPoet.I18n.t('tabSystemInfoTitle'),
    link: '/systemInfo',
  },
  {
    name: 'yourPrivacy',
    label: MailPoet.I18n.t('tabYourPrivacyTitle'),
    link: '/yourPrivacy',
  },
];

function Tabs(props) {
  const tabLinks = tabs.map((tab) => {
    const tabClasses = classNames(
      'nav-tab',
      { 'nav-tab-active': (props.tab === tab.name) }
    );

    return (
      <Link
        key={`tab-${tab.name}`}
        className={tabClasses}
        to={tab.link}
      >{ tab.label }</Link>
    );
  });

  return (
    <h2 className="nav-tab-wrapper">
      { tabLinks }
    </h2>
  );
}

Tabs.propTypes = { tab: PropTypes.string };
Tabs.defaultProps = { tab: 'knowledgeBase' };

module.exports = Tabs;
