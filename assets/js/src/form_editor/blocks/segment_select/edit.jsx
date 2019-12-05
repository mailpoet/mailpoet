import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
  CheckboxControl,
  SelectControl,
  BaseControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const SegmentSelectEdit = ({ attributes, setAttributes }) => {
  const allSegments = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableSegments(),
    []
  );

  const segmentsListToBeAdded = allSegments.map((segment) => ({
    label: segment.name,
    value: segment.id,
  }))
    .filter((segment) => !attributes.values.find((s) => s.id === segment.value));

  const addSegment = (segmentId) => {
    const segment = allSegments.find((s) => s.id === segmentId);
    setAttributes({
      values: [
        ...attributes.values,
        {
          name: segment.name,
          isChecked: false,
          id: segmentId,
        },
      ],
    });
  };

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_first_name_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
          {segmentsListToBeAdded.length ? (
            <SelectControl
              label={`${MailPoet.I18n.t('blockSegmentSelectListLabel')}:`}
              options={[
                {
                  label: MailPoet.I18n.t('settingsPleaseSelectList'),
                  value: null,
                },
                ...segmentsListToBeAdded,
              ]}
              onChange={addSegment}
            />
          ) : null}
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  const renderValues = () => {
    if (attributes.values.length === 0) {
      return (<p className="mailpoet_error">{MailPoet.I18n.t('blockSegmentSelectNoLists')}</p>);
    }
    return attributes.values.map((value) => (
      <CheckboxControl
        label={value.name}
        checked={!!value.isChecked}
        disabled
        key={value.id}
      />
    ));
  };

  return (
    <>
      {inspectorControls}
      <BaseControl
        label={attributes.label}
      />
      {renderValues()}
    </>
  );
};

SegmentSelectEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    values: PropTypes.arrayOf(PropTypes.shape({
      isChecked: PropTypes.boolean,
      name: PropTypes.string.isRequired,
      id: PropTypes.string.isRequired,
    })).isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SegmentSelectEdit;
