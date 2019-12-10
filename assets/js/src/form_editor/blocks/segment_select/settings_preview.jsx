import React, { useRef, useCallback, useState } from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl, Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import Backend from 'react-dnd-html5-backend';

const PreviewItem = ({
  segment,
  index,
  moveItem,
  removeSegment,
  onCheck,
  dragFinished,
}) => {
  const ref = useRef(null);
  const [, drop] = useDrop({
    accept: 'item',
    hover(item, monitor) {
      if (!ref.current) {
        return;
      }
      const dragIndex = item.index;
      const hoverIndex = index;
      // Don't replace items with themselves
      if (dragIndex === hoverIndex) {
        return;
      }
      // Determine rectangle on screen
      const hoverBoundingRect = ref.current.getBoundingClientRect();
      // Get vertical middle
      const hoverMiddleY = (hoverBoundingRect.bottom - hoverBoundingRect.top) / 2;
      // Determine mouse position
      const clientOffset = monitor.getClientOffset();
      // Get pixels to the top
      const hoverClientY = clientOffset.y - hoverBoundingRect.top;
      // Only perform the move when the mouse has crossed half of the items height
      // When dragging downwards, only move when the cursor is below 50%
      // When dragging upwards, only move when the cursor is above 50%
      // Dragging downwards
      if (dragIndex < hoverIndex && hoverClientY < hoverMiddleY) {
        return;
      }
      // Dragging upwards
      if (dragIndex > hoverIndex && hoverClientY > hoverMiddleY) {
        return;
      }
      // Time to actually perform the action
      moveItem(dragIndex, hoverIndex);
      // Note: we're mutating the monitor item here!
      // Generally it's better to avoid mutations,
      // but it's good here for the sake of performance
      // to avoid expensive index searches.
      // eslint-disable-next-line no-param-reassign
      item.index = hoverIndex;
    },
  });
  const [{ isDragging }, drag] = useDrag({
    item: { type: 'item', id: segment.id, index },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
    end() {
      dragFinished();
    },
  });
  const opacity = isDragging ? 0.2 : 1;
  drag(drop(ref));
  return (
    <div
      className="mailpoet-form-segments-settings-list"
      key={segment.id}
      ref={ref}
      style={{ opacity }}
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
};

PreviewItem.propTypes = {
  segment: PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
    id: PropTypes.string.isRequired,
  }).isRequired,
  onCheck: PropTypes.func.isRequired,
  moveItem: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
  index: PropTypes.number.isRequired,
  dragFinished: PropTypes.func.isRequired,
};

const Preview = ({
  segments,
  updateSegment,
  removeSegment,
  onSegmentsReorder,
}) => {
  const [segmentsWhileMoved, setSegments] = useState(segments);
  const moveItem = useCallback(
    (dragIndex, hoverIndex) => {
      const result = Array.from(segmentsWhileMoved);
      const [removed] = result.splice(dragIndex, 1);
      result.splice(hoverIndex, 0, removed);

      setSegments(result);
    },
    [segmentsWhileMoved, setSegments],
  );

  if (segmentsWhileMoved.length === 0) {
    return null;
  }

  const onCheck = (segmentId, isChecked) => {
    const segment = segmentsWhileMoved.find((s) => s.id === segmentId);
    segment.isChecked = isChecked;
    updateSegment(segment);
  };

  return (
    <DndProvider backend={Backend}>
      {segmentsWhileMoved.map((segment, index) => (
        <PreviewItem
          key={segment.id}
          index={index}
          segment={segment}
          moveItem={moveItem}
          onCheck={onCheck}
          removeSegment={removeSegment}
          dragFinished={() => onSegmentsReorder(segmentsWhileMoved)}
        />
      ))}
    </DndProvider>
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
