import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
  CheckboxControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const SegmentSelectEdit = ({ attributes, setAttributes }) => {
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
      <p>
        {attributes.label}
      </p>
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
