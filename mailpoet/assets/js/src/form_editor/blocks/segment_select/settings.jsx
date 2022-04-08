import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { InspectorControls } from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  PanelRow,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import Preview from './settings_preview.jsx';

const findSegment = (segments, segmentId) =>
  segments.find((s) => s.id === segmentId);

function SegmentSelectSettings({
  label,
  onLabelChanged,
  segmentsAddedIntoSelection,
  addSegmentIntoSelection,
  setNewSelection,
}) {
  const allSegments = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableSegments(),
    [],
  );

  const segmentsListToBeAdded = allSegments
    .map((segment) => ({
      label: segment.name,
      value: segment.id,
    }))
    .filter(
      (segment) => !findSegment(segmentsAddedIntoSelection, segment.value),
    );

  const addSegment = (segmentId) => {
    const segment = findSegment(allSegments, segmentId);
    addSegmentIntoSelection({
      name: segment.name,
      isChecked: false,
      id: segmentId,
    });
  };

  const updateSegment = (segment) => {
    setNewSelection(
      segmentsAddedIntoSelection.map((segmentInSelection) => {
        if (segment.id !== segmentInSelection) {
          return segmentInSelection;
        }
        return segment;
      }),
    );
  };

  const removeSegment = (segmentId) => {
    setNewSelection(
      segmentsAddedIntoSelection.filter((segment) => segmentId !== segment.id),
    );
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
          <PanelRow>
            {segmentsListToBeAdded.length ? (
              <SelectControl
                label={`${MailPoet.I18n.t('blockSegmentSelectListLabel')}:`}
                data-automation-id="select_list_selections_list"
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
          <Preview
            segments={segmentsAddedIntoSelection}
            updateSegment={updateSegment}
            removeSegment={removeSegment}
            onSegmentsReorder={setNewSelection}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );
}

SegmentSelectSettings.propTypes = {
  label: PropTypes.string.isRequired,
  onLabelChanged: PropTypes.func.isRequired,
  addSegmentIntoSelection: PropTypes.func.isRequired,
  setNewSelection: PropTypes.func.isRequired,
  segmentsAddedIntoSelection: PropTypes.arrayOf(
    PropTypes.shape({
      name: PropTypes.string.isRequired,
      isChecked: PropTypes.bool,
      id: PropTypes.string.isRequired,
    }).isRequired,
  ).isRequired,
};

export default SegmentSelectSettings;
