import React from 'react';
import PropTypes from 'prop-types';
import { CheckboxControl, Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

const getItemStyle = (isDragging, draggableStyle) => ({
  userSelect: 'none',
  background: isDragging ? 'lightblue' : 'rgba(0,0,0,0)',
  ...draggableStyle,
});

const getListStyle = (isDraggingOver) => ({
  background: isDraggingOver ? 'lightgrey' : 'rgba(0,0,0,0)',
});

const reorder = (list, startIndex, endIndex) => {
  const result = Array.from(list);
  const [removed] = result.splice(startIndex, 1);
  result.splice(endIndex, 0, removed);

  return result;
};

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

  const onDragEnd = (result) => {
    if (!result.destination) {
      return;
    }

    onSegmentsReorder(reorder(
      segments,
      result.source.index,
      result.destination.index
    ));
  };

  return (
    <DragDropContext onDragEnd={onDragEnd}>
      <Droppable droppableId="droppable">
        {(providedDroppable, snapshotDroppable) => (
          <div
            {...providedDroppable.droppableProps}
            ref={providedDroppable.innerRef}
            style={getListStyle(snapshotDroppable.isDraggingOver)}
          >
            {segments.map((segment, index) => (
              <Draggable key={segment.id} draggableId={segment.id} index={index}>
                {(providedDraggable, snapshotDraggable) => (
                  <div
                    className="mailpoet-form-segments-settings-list"
                    key={segment.id}
                    ref={providedDraggable.innerRef}
                    {...providedDraggable.draggableProps}
                    {...providedDraggable.dragHandleProps}
                    style={getItemStyle(
                      snapshotDraggable.isDragging,
                      providedDraggable.draggableProps.style
                    )}
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
                )}
              </Draggable>
            ))}
            {providedDroppable.placeholder}
          </div>
        )}
      </Droppable>
    </DragDropContext>
  );
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
