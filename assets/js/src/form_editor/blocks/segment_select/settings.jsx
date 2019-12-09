import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { InspectorControls } from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const SegmentSelectSettings = ({
  label,
  onLabelChanged,
  segmentsAddedIntoSelection,
  addSegmentIntoSelection,
}) => {
  const allSegments = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableSegments(),
    []
  );

  const segmentsListToBeAdded = allSegments.map((segment) => ({
    label: segment.name,
    value: segment.id,
  }))
    .filter((segment) => !segmentsAddedIntoSelection.find((s) => s.id === segment.value));

  const addSegment = (segmentId) => {
    const segment = allSegments.find((s) => s.id === segmentId);
    addSegmentIntoSelection({
      name: segment.name,
      isChecked: false,
      id: segmentId,
    });
  };

  return (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={label}
            data-automation-id="settings_first_name_label_input"
            onChange={onLabelChanged}
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
};

SegmentSelectSettings.propTypes = {
  label: PropTypes.string.isRequired,
  onLabelChanged: PropTypes.func.isRequired,
  addSegmentIntoSelection: PropTypes.func.isRequired,
  segmentsAddedIntoSelection: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.boolean,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
};

export default SegmentSelectSettings;
