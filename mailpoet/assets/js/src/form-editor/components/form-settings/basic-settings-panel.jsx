import {
  Panel,
  PanelBody,
  RadioControl,
  SelectControl,
  TextareaControl,
  ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { isEqual } from 'lodash';
import { FormTitle } from '../form-title';
import { storeName } from '../../store';
import { ItemSelectControl } from './item-select-control';

function BasicSettingsPanel({ onToggle, isOpened }) {
  const { settings, segments, pages, missingListError, isFormEnabled } =
    useSelect(
      (select) => ({
        settings: select(storeName).getFormSettings(),
        segments: select(storeName).getAllAvailableSegments(),
        pages: select(storeName).getAllWPPages(),
        missingListError: select(storeName).getNotice('missing-lists'),
        isFormEnabled: select(storeName).isFormEnabled(),
      }),
      [],
    );

  const { changeFormSettings, toggleForm } = useDispatch(storeName);

  const segmentItems = segments
    .filter((seg) => !seg.deleted_at && seg.type === 'default')
    .map((seg) => ({
      id: seg.id.toString(),
      name: `${seg.name} (${parseInt(seg.subscribers, 10).toLocaleString()})`,
    }));

  const onSegmentsChange = (segmentIds) => {
    if (isEqual(settings.segments, segmentIds)) {
      return;
    }
    changeFormSettings({
      ...settings,
      segments: segmentIds,
    });
  };

  const onSuccessTypeChange = (onSuccess) => {
    changeFormSettings({
      ...settings,
      on_success: onSuccess,
    });
  };

  const onSuccessMessageChange = (message) => {
    changeFormSettings({
      ...settings,
      success_message: message,
    });
  };

  const onSuccessPageChange = (message) => {
    changeFormSettings({
      ...settings,
      success_page: message,
    });
  };

  const selectedSegments = settings.segments
    ? segmentItems.filter((seg) => settings.segments.includes(seg.id))
    : [];
  const shouldDisplayMissingListError =
    missingListError && !selectedSegments.length;
  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formSettings')}
        opened={isOpened}
        onToggle={onToggle}
      >
        <FormTitle />
        <ToggleControl
          label={MailPoet.I18n.t('displayForm')}
          checked={isFormEnabled}
          onChange={toggleForm}
        />
        <div
          className={classnames('mailpoet-form-segments', {
            'mailpoet-form-missing-lists': shouldDisplayMissingListError,
          })}
        >
          <ItemSelectControl
            label={MailPoet.I18n.t('settingsListLabel')}
            placeholder={MailPoet.I18n.t('settingsPleaseSelectList')}
            items={segmentItems}
            selected={selectedSegments.map((seg) => seg.id)}
            onChange={onSegmentsChange}
          />
          {shouldDisplayMissingListError ? (
            <span className="mailpoet-form-lists-error">
              {MailPoet.I18n.t('settingsPleaseSelectList')}
            </span>
          ) : null}
        </div>
        <RadioControl
          className="mailpoet-form-inline-radios__control"
          onChange={onSuccessTypeChange}
          selected={settings.on_success || 'message'}
          label={MailPoet.I18n.t('settingsAfterSubmit')}
          options={[
            {
              label: MailPoet.I18n.t('settingsShowMessage'),
              value: 'message',
            },
            {
              label: MailPoet.I18n.t('settingsGoToPage'),
              value: 'page',
            },
          ]}
        />
        {settings.on_success === 'page' ? (
          <SelectControl
            value={settings.success_page}
            options={pages.map((page) => ({
              value: page.id.toString(),
              label: page.name,
            }))}
            onChange={onSuccessPageChange}
          />
        ) : (
          <TextareaControl
            value={settings.success_message}
            onChange={onSuccessMessageChange}
            rows={3}
          />
        )}
      </PanelBody>
    </Panel>
  );
}

BasicSettingsPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};
BasicSettingsPanel.displayName = 'FormEditorBasicSettingsPanel';
export { BasicSettingsPanel };
