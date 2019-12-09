import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { partial } from 'lodash';
import { InspectorControls } from '@wordpress/block-editor';
import {
  CheckboxControl,
  Panel,
  PanelBody,
  PanelRow,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const findSegment = (segments, segmentId) => segments.find((s) => s.id === segmentId);

const Preview = ({ segments, updateSegment }) => {
  if (segments.length === 0) {
    return null;
  }

  const onCheck = (segmentId, isChecked) => {
    const segment = findSegment(segments, segmentId);
    segment.isChecked = isChecked;
    updateSegment(segment);
  };

  return segments.map((segment) => (
    <CheckboxControl
      label={segment.name}
      checked={!!segment.isChecked}
      onChange={partial(onCheck, segment.id)}
      key={segment.id}
    />
  ));
};

Preview.propTypes = {
  segments: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.boolean,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
  updateSegment: PropTypes.func.isRequired,
};

const SegmentSelectSettings = ({
  label,
  onLabelChanged,
  segmentsAddedIntoSelection,
  addSegmentIntoSelection,
  setNewSelection,
}) => {
  const allSegments = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableSegments(),
    []
  );

  const segmentsListToBeAdded = allSegments.map((segment) => ({
    label: segment.name,
    value: segment.id,
  }))
    .filter((segment) => !findSegment(segmentsAddedIntoSelection, segment.value));

  const addSegment = (segmentId) => {
    const segment = findSegment(allSegments, segmentId);
    addSegmentIntoSelection({
      name: segment.name,
      isChecked: false,
      id: segmentId,
    });
  };

  const updateSegment = (segment) => {
    setNewSelection(segmentsAddedIntoSelection.map((segmentInSelection) => {
      if (segment.id !== segmentInSelection) {
        return segmentInSelection;
      }
      return segment;
    }));
  };

  return (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <PanelRow>
            <TextControl
              label={MailPoet.I18n.t('label')}
              value={label}
              data-automation-id="settings_first_name_label_input"
              onChange={onLabelChanged}
            />
          </PanelRow>
          <Preview
            segments={segmentsAddedIntoSelection}
            updateSegment={updateSegment}
          />
          <PanelRow>
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
          </PanelRow>
        </PanelBody>
      </Panel>
    </InspectorControls>
  );
};

SegmentSelectSettings.propTypes = {
  label: PropTypes.string.isRequired,
  onLabelChanged: PropTypes.func.isRequired,
  addSegmentIntoSelection: PropTypes.func.isRequired,
  setNewSelection: PropTypes.func.isRequired,
  segmentsAddedIntoSelection: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.boolean,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
};

export default SegmentSelectSettings;
