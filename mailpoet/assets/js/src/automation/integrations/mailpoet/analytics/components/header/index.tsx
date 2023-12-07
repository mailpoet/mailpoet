import {
  Button,
  ButtonGroup,
  Dropdown,
  MenuGroup,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useSelect } from '@wordpress/data';
import { chevronDown, Icon } from '@wordpress/icons';
import { Filter } from './filter';
import { MailPoet } from '../../../../../../mailpoet';
import { storeName as editorStoreName } from '../../../../../editor/store/constants';
import { AutomationStatus } from '../../../../../listing/automation';
import {
  DeactivateButton,
  DeactivateNowButton,
} from '../../../../../editor/components/header';
import { TrashButton } from '../../../../../editor/components/actions/trash-button';
import { EditorNotices } from '../../../../../editor/components/notices';

export function Header(): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));
  return (
    <header className="mailpoet-analytics-header">
      <Filter />
      <EditorNotices />
      <Dropdown
        focusOnMount={false}
        popoverProps={{ placement: 'bottom-end' }}
        renderToggle={({ isOpen, onToggle }) => (
          <ButtonGroup>
            <Button
              href={addQueryArgs(MailPoet.urls.automationEditor, {
                id: automation.id,
              })}
              variant="primary"
            >
              {__('Edit automation', 'mailpoet')}
            </Button>
            <Button onClick={onToggle} aria-expanded={isOpen} variant="primary">
              &nbsp;
              <Icon icon={chevronDown} size={18} />
            </Button>
          </ButtonGroup>
        )}
        renderContent={() => (
          <MenuGroup>
            {automation.status === AutomationStatus.ACTIVE && (
              <DeactivateButton />
            )}
            {automation.status === AutomationStatus.DEACTIVATING && (
              <DeactivateNowButton />
            )}
            <TrashButton
              performActionAfterDelete={() => {
                window.location.href = MailPoet.urls.automationListing;
              }}
            />
          </MenuGroup>
        )}
      />
    </header>
  );
}
