import {
  Button,
  DropdownMenu,
  MenuGroup,
  ToolbarItem,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { moreVertical, plus } from '@wordpress/icons';
import { __, _x } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { FeatureToggle } from './feature-toggle';
import { HistoryUndo } from './history-undo';
import { HistoryRedo } from './history-redo';
import { storeName } from '../store';

function Header({ isInserterOpened, setIsInserterOpened }) {
  const sidebarOpened = useSelect(
    (select) => select(storeName).getSidebarOpened(),
    [],
  );
  const isFormSaving = useSelect(
    (select) => select(storeName).getIsFormSaving(),
    [],
  );
  const isPreview = useSelect(
    (select) => select(storeName).getIsPreviewShown(),
    [],
  );
  const isFullscreen = useSelect(
    (select) => select(storeName).isFullscreenEnabled(),
    [],
  );
  const { toggleSidebar, saveForm, showPreview, toggleFullscreen } =
    useDispatch(storeName);

  return (
    <div className="edit-post-header">
      <div className="edit-post-header__settings">
        <Button
          isSecondary
          onClick={showPreview}
          isPressed={isPreview}
          className="mailpoet-preview-button"
          data-automation-id="form_preview_button"
        >
          {__('Preview')}
        </Button>
        <Button
          isPrimary
          className="editor-post-publish-button"
          data-automation-id="form_save_button"
          isBusy={isFormSaving}
          onClick={saveForm}
        >
          {isFormSaving ? `${__('Saving')}` : __('Save')}
        </Button>
        <Button
          icon="admin-generic"
          label={MailPoet.I18n.t('formSettings')}
          tooltipPosition="down"
          onClick={() => toggleSidebar(!sidebarOpened)}
          isPressed={sidebarOpened}
          className="mailpoet-editor-header-button"
        />
        <DropdownMenu
          icon={moreVertical}
          label={__('More tools & options')}
          className="edit-post-more-menu"
          popoverProps={{
            className: 'edit-post-more-menu__content',
          }}
        >
          {() => (
            <MenuGroup
              label={_x('View', 'noun')}
              className="mailpoet-dropdown-menu-group"
            >
              <FeatureToggle
                shortcut="Ctrl+Shift+Alt+F"
                label={__('Fullscreen mode')}
                info={__('Work without distraction')}
                isActive={isFullscreen}
                onToggle={() => toggleFullscreen(!isFullscreen)}
              />
            </MenuGroup>
          )}
        </DropdownMenu>
      </div>
      <div className="edit-post-header__toolbar">
        <div className="toolbar edit-post-header-toolbar edit-post-header-toolbar__left">
          <Button
            isSecondary
            href="?page=mailpoet-forms#/"
            className="mailpoet-editor-header-button"
          >
            {MailPoet.I18n.t('back')}
          </Button>
          <ToolbarItem
            as={Button}
            data-automation-id="form_inserter_open"
            className="edit-post-header-toolbar__inserter-toggle"
            isPrimary
            isPressed={isInserterOpened}
            onClick={() => setIsInserterOpened(!isInserterOpened)}
            icon={plus}
            label={_x('Add block', 'Generic label for block inserter button')}
          />
          <HistoryUndo data-automation-id="form_undo_button" />
          <HistoryRedo data-automation-id="form_redo_button" />
        </div>
      </div>
    </div>
  );
}

Header.propTypes = {
  isInserterOpened: PropTypes.bool.isRequired,
  setIsInserterOpened: PropTypes.func.isRequired,
};
Header.displayName = 'FormEditorHeader';
export { Header };
