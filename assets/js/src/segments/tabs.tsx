import React from 'react';
import classnames from 'classnames';
import MailPoet from 'mailpoet';
import { Link, useLocation } from 'react-router-dom';

export default () => {
  const { pathname } = useLocation();
  const [, current] = pathname.split('/');

  return (
    <h2 className="nav-tab-wrapper">
      <Link
        to="/"
        className={classnames('nav-tab', { 'nav-tab-active': current !== 'segments' })}
      >
        {MailPoet.I18n.t('pageTitle')}
      </Link>
      <Link
        to="/segments"
        className={classnames('nav-tab', { 'nav-tab-active': current === 'segments' })}
      >
        {MailPoet.I18n.t('pageTitleSegments')}
      </Link>
    </h2>
  );
};
