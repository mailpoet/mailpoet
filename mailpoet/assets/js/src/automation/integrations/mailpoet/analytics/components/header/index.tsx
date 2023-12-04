import {
  Button,
  ButtonGroup,
  Dropdown,
  MenuGroup,
  MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useSelect } from '@wordpress/data';
import { chevronDown, Icon } from '@wordpress/icons';
import { Filter } from './filter';
import { MailPoet } from '../../../../../../mailpoet';
import { storeName as editorStoreName } from '../../../../../editor/store/constants';

export function Header(): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));
  return (
    <header className="mailpoet-analytics-header">
      <Filter />
      <Dropdown
        focusOnMount={false}
        popoverProps={{ placement: 'bottom-end' }}
        renderToggle={({ isOpen, onToggle }) => (
          <ButtonGroup>
            <Button
              href={addQueryArgs(MailPoet.urls.automationEditor, {
                id: automation.id,
              })}
              isPrimary
            >
              {__('Edit automation', 'mailpoet')}
            </Button>
            <Button onClick={onToggle} aria-expanded={isOpen} variant="primary">
              <br />
              <Icon icon={chevronDown} size={18} />
            </Button>
          </ButtonGroup>
        )}
        renderContent={() => (
          <MenuGroup>
            <MenuItem variant="tertiary" onClick={() => {}}>
              {__('Deactivate', 'mailpoet')}
            </MenuItem>
            <MenuItem isDestructive onClick={() => {}}>
              {__('Move to Trash', 'mailpoet')}
            </MenuItem>
          </MenuGroup>
        )}
      />
    </header>
  );
}
