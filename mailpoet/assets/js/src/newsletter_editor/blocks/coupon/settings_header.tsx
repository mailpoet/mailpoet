import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { useState } from 'react';

function SettingsHeader({ source, onClick }) {
  const [activeTab, setActiveTab] = useState(source);

  return (
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
      <ul>
        <li>
          <Button
            onClick={() => {
              onClick('allCoupons');
              setActiveTab('allCoupons');
            }}
            className={classnames('edit-post-sidebar__panel-tab', {
              'is-active': activeTab === 'allCoupons',
            })}
            data-label={MailPoet.I18n.t('allCoupons')}
          >
            {MailPoet.I18n.t('allCoupons')}
          </Button>
        </li>
        <li>
          <Button
            onClick={() => {
              onClick('createNew');
              setActiveTab('createNew');
            }}
            className={classnames('edit-post-sidebar__panel-tab', {
              'is-active': activeTab === 'createNew',
            })}
            data-label={MailPoet.I18n.t('createNew')}
          >
            {MailPoet.I18n.t('createNew')}
          </Button>
        </li>
      </ul>
    </div>
  );
}

export { SettingsHeader };
