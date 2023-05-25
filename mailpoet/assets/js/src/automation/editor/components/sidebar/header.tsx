import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
  stepSidebarKey,
  storeName,
  automationSidebarKey,
  aiSidebarKey,
} from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/sidebar/settings-header/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/sidebar/settings-header/index.js

type Props = {
  sidebarKey: string;
};

export function Header({ sidebarKey }: Props): JSX.Element {
  const { openSidebar } = useDispatch(storeName);
  const openAutomationSettings = () => openSidebar(automationSidebarKey);
  const openStepSettings = () => openSidebar(stepSidebarKey);
  const openAiSettings = () => openSidebar(aiSidebarKey);

  const [automationAriaLabel, automationActiveClass] =
    sidebarKey === automationSidebarKey
      ? [__('Automation (selected)', 'mailpoet'), 'is-active']
      : [__('Automation', 'mailpoet'), ''];

  const [stepAriaLabel, stepActiveClass] =
    sidebarKey === stepSidebarKey
      ? [__('Step (selected)', 'mailpoet'), 'is-active']
      : [__('Step', 'mailpoet'), ''];

  const [aiAriaLabel, aiActiveClass] =
    sidebarKey === aiSidebarKey
      ? [__('AI (selected)', 'mailpoet'), 'is-active']
      : [__('AI', 'mailpoet'), ''];

  return (
    <ul>
      <li>
        <Button
          onClick={openAutomationSettings}
          className={`edit-site-sidebar-edit-mode__panel-tab ${automationActiveClass}`}
          aria-label={automationAriaLabel}
          data-label={__('Automation', 'mailpoet')}
        >
          {__('Automation', 'mailpoet')}
        </Button>
      </li>
      <li>
        <Button
          onClick={openStepSettings}
          className={`edit-site-sidebar-edit-mode__panel-tab ${stepActiveClass}`}
          aria-label={stepAriaLabel}
          data-label={__('Step', 'mailpoet')}
        >
          {__('Step', 'mailpoet')}
        </Button>
      </li>
      <li>
        <Button
          onClick={openAiSettings}
          className={`edit-site-sidebar-edit-mode__panel-tab ${aiActiveClass}`}
          aria-label={aiAriaLabel}
          data-label={__('AI', 'mailpoet')}
        >
          {__('AI', 'mailpoet')}
        </Button>
      </li>
    </ul>
  );
}
