import React from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl } from '@wordpress/components';
import { partial } from 'lodash';

const Preview = ({ segments, updateSegment, removeSegment }) => {
  if (segments.length === 0) {
    return null;
  }

  const onCheck = (segmentId, isChecked) => {
    const segment = segments.find((s) => s.id === segmentId);
    segment.isChecked = isChecked;
    updateSegment(segment);
  };

  return segments.map((segment) => (
    <div className="mailpoet-form-segments-settings-list" key={segment.id}>
      <CheckboxControl
        label={segment.name}
        checked={!!segment.isChecked}
        onChange={partial(onCheck, segment.id)}
        key={`check-${segment.id}`}
      />
      <span
        role="button"
        className="mailpoet-form-segments-segment-remove mailpoet_error"
        onClick={partial(removeSegment, segment.id)}
        key={`remove-${segment.id}`}
        tabIndex={0}
        onKeyDown={(event) => {
          if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
          ) {
            event.preventDefault();
            partial(removeSegment, segment.id);
          }
        }}
      >
        ✗
      </span>
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
};

export default Preview;
