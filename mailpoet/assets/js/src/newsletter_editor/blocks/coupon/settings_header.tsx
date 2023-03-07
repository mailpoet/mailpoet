import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

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
            data-label={__('All coupons', 'mailpoet')}
          >
            {__('All coupons', 'mailpoet')}
          </Button>
        </li>
        <li>
          <Button
            onClick={() => onClick(SettingsTabs.createNew)}
            className={classnames('edit-post-sidebar__panel-tab', {
              'is-active': activeTab === SettingsTabs.createNew,
            })}
            data-label={__('Create new', 'mailpoet')}
          >
            {__('Create new', 'mailpoet')}
          </Button>
        </li>
      </ul>
    </div>
  );
}

export { SettingsHeader };
