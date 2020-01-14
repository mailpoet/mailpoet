import React, {
  useState,
  useEffect,
} from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl, Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { ReactSortable } from 'react-sortablejs';

const PreviewItem = ({
  segment,
  removeSegment,
  onCheck,
}) => (
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
);

PreviewItem.propTypes = {
  segment: PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
    id: PropTypes.string.isRequired,
  }).isRequired,
  onCheck: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
};

const Preview = ({
  segments,
  updateSegment,
  removeSegment,
  onSegmentsReorder,
}) => {
  const [segmentsWhileMoved, setSegments] = useState(segments);

  useEffect(() => {
    setSegments(segments);
  }, [segments]);

  if (segmentsWhileMoved.length === 0) {
    return null;
  }

  const onCheck = (segmentId, isChecked) => {
    const segment = segmentsWhileMoved.find((s) => s.id === segmentId);
    segment.isChecked = isChecked;
    updateSegment(segment);
  };

  return (
    <ReactSortable list={segmentsWhileMoved} setList={onSegmentsReorder}>
      {segmentsWhileMoved.map((segment, index) => (
        <PreviewItem
          key={segment.id}
          index={index}
          segment={segment}
          onCheck={onCheck}
          removeSegment={removeSegment}
        />
      ))}
    </ReactSortable>
  );
};

Preview.propTypes = {
  segments: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
  updateSegment: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
  onSegmentsReorder: PropTypes.func.isRequired,
};

export default Preview;
