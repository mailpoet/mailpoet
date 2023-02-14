import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { MailPoet } from 'mailpoet';

export enum SettingsTabs {
  allCoupons = 'allCoupons',
  createNew = 'createNew',
}

function SettingsHeader({ activeTab, onClick }) {
  return (
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
      <ul>
        <li>
          <Button
            onClick={() => onClick(SettingsTabs.allCoupons)}
            className={classnames('edit-post-sidebar__panel-tab', {
              'is-active': activeTab === SettingsTabs.allCoupons,
            })}
            data-label={MailPoet.I18n.t('allCoupons')}
          >
            {MailPoet.I18n.t('allCoupons')}
          </Button>
        </li>
        <li>
          <Button
            onClick={() => onClick(SettingsTabs.createNew)}
            className={classnames('edit-post-sidebar__panel-tab', {
              'is-active': activeTab === SettingsTabs.createNew,
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
