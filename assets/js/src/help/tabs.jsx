import React from 'react';
import { Link } from 'react-router';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const tabs = [
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
    name: 'knowledgeBase',
    label: MailPoet.I18n.t('tabKnowledgeBaseTitle'),
    link: '/knowledgeBase',
  },
];

function Tabs(props) {
  const tabLinks = tabs.map((tab, index) => {
    const tabClasses = classNames(
      'nav-tab',
      { 'nav-tab-active': (props.tab === tab.name) }
    );

    return (
      <Link
        key={'tab-' + index}
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

Tabs.propTypes = { tab: React.PropTypes.string };
Tabs.defaultProps = { tab: 'systemStatus' };

module.exports = Tabs;
