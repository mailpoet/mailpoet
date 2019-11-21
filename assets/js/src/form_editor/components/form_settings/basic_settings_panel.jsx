import {
  BaseControl,
  Panel,
  PanelBody,
  RadioControl,
  TextareaControl,
  SelectControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import React from 'react';
import MailPoet from 'mailpoet';
import Selection from '../../../form/fields/selection.jsx';

export default () => {
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const segments = useSelect(
    (select) => select('mailpoet-form-editor').getSegments(),
    []
  );

  const pages = useSelect(
    (select) => select('mailpoet-form-editor').getPages(),
    []
  );

  const missingListError = useSelect(
    (select) => select('mailpoet-form-editor').getNotice('missing-lists'),
    []
  );

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const onSegmentsChange = (e) => {
    changeFormSettings({
      ...settings,
      segments: e.target.value,
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
    ? segments.filter((seg) => (settings.segments.includes(seg.id.toString())))
    : [];
  const shouldDisplayMissingListError = missingListError && !selectedSegments.length;
  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettings')}>
        <BaseControl
          label={MailPoet.I18n.t('settingsListLabel')}
          className={shouldDisplayMissingListError ? 'mailpoet-form-missing-lists' : null}
        >
          {shouldDisplayMissingListError ? (
            <span className="mailpoet-form-lists-error">{MailPoet.I18n.t('settingsPleaseSelectList')}</span>
          ) : null }
          <Selection
            item={{
              segments: selectedSegments,
            }}
            onValueChange={onSegmentsChange}
            field={{
              id: 'segments',
              name: 'segments',
              values: segments,
              multiple: true,
              placeholder: MailPoet.I18n.t('settingsPleaseSelectList'),
              getLabel: (seg) => (`${seg.name} (${parseInt(seg.subscribers, 10).toLocaleString()})`),
              filter: (seg) => (!!(!seg.deleted_at && seg.type === 'default')),
            }}
          />
        </BaseControl>
        <RadioControl
          className="mailpoet-form-success-types__control"
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
            options={pages.map((page) => ({ value: page.id.toString(), label: page.title }))}
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
};
