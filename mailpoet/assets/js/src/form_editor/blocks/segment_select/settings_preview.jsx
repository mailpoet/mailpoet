import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl, Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

function PreviewItem({ segment, removeSegment, onCheck }) {
  return (
    <div className="mailpoet-form-segments-settings-list" key={segment.id}>
      <CheckboxControl
        label={segment.name}
        defaultChecked={!!segment.isChecked}
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
}

PreviewItem.propTypes = {
  segment: PropTypes.shape({
    name: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
    id: PropTypes.string.isRequired,
  }).isRequired,
  onCheck: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
};

function Preview({
  segments,
  updateSegment,
  removeSegment,
  onSegmentsReorder,
}) {
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

  const onDragEnd = (result) => {
    const from = result.source.index;
    const to = result.destination.index;
    const newValues = [...segmentsWhileMoved];
    const [movedItem] = newValues.splice(from, 1);
    newValues.splice(to, 0, movedItem);
    setSegments(newValues);
    onSegmentsReorder(newValues);
  };

  const renderItems = () =>
    segmentsWhileMoved.map((segment, index) => (
      <Draggable key={segment.id} draggableId={segment.id} index={index}>
        {(provided) => (
          <div
            ref={provided.innerRef}
            {...provided.draggableProps}
            {...provided.dragHandleProps}
          >
            <PreviewItem
              key={segment.id}
              index={index}
              segment={segment}
              onCheck={onCheck}
              removeSegment={removeSegment}
            />
          </div>
        )}
      </Draggable>
    ));
  return (
    <DragDropContext onDragEnd={onDragEnd}>
      <Droppable droppableId="droppable">
        {(provided) => (
          <div {...provided.droppableProps} ref={provided.innerRef}>
            {renderItems()}
            {provided.placeholder}
          </div>
        )}
      </Droppable>
    </DragDropContext>
  );
}

Preview.propTypes = {
  segments: PropTypes.arrayOf(
    PropTypes.shape({
      name: PropTypes.string.isRequired,
      isChecked: PropTypes.bool,
      id: PropTypes.string.isRequired,
    }).isRequired,
  ).isRequired,
  updateSegment: PropTypes.func.isRequired,
  removeSegment: PropTypes.func.isRequired,
  onSegmentsReorder: PropTypes.func.isRequired,
};

export default Preview;
