import React from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl, Dashicon } from '@wordpress/components';
import { partial } from 'lodash';

const Preview = ({
  segments,
  updateSegment,
  removeSegment,
  onSegmentsReorder,
}) => {
  if (segments.length === 0) {
    return null;
  }

  const onCheck = (segmentId, isChecked) => {
    const segment = segments.find((s) => s.id === segmentId);
    segment.isChecked = isChecked;
    updateSegment(segment);
  };

  return segments.map((segment) => (
    <div
      className="mailpoet-form-segments-settings-list"
      key={segment.id}
    >
      <CheckboxControl
        label={segment.name}
        checked={!!segment.isChecked}
        onChange={partial(onCheck, segment.id)}
        key={`check-${segment.id}`}
      />
      <Dashicon
        icon="no-alt"
        color="#900"
        className="mailpoet-form-segments-segment-remove"
        onClick={partial(removeSegment, segment.id)}
      />
    </div>
  ));
};

Preview.propTypes = {
  segments: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.boolean,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
  updateSegment: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
  onSegmentsReorder: PropTypes.func.isRequired,
};

export default Preview;
