import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import {
  mainSidebarEmailKey,
  mainSidebarBlockKey,
  storeName,
} from '../../store';

type Props = {
  sidebarKey: string;
};

export function Header({ sidebarKey }: Props) {
  const { openSidebar } = useDispatch(storeName);
  return (
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
      <ul>
        <li>
          <button
            onClick={() => {
              openSidebar(mainSidebarEmailKey);
            }}
            className={classnames(
              'components-button edit-post-sidebar__panel-tab',
              { 'is-active': sidebarKey === mainSidebarEmailKey },
            )}
            data-automation-id="email_settings_tab"
            type="button"
          >
            {__('Email', 'mailpoet')}
          </button>
        </li>
        <li>
          <button
            onClick={() => {
              openSidebar(mainSidebarBlockKey);
            }}
            className={classnames(
              'components-button edit-post-sidebar__panel-tab',
              { 'is-active': sidebarKey === mainSidebarBlockKey },
            )}
            data-automation-id="mailpoet_block_settings_tab"
            type="button"
          >
            {__('Block')}
          </button>
        </li>
      </ul>
    </div>
  );
}
