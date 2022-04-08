import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

function PreviewItem({ value, remove, onUpdate, onCheck, index }) {
  return (
    <div
      className="mailpoet-form-segments-settings-list"
      data-automation-id="custom_field_value_settings"
      key={value.id}
    >
      <input
        type="checkbox"
        defaultChecked={value.isChecked || false}
        onChange={(event) => onCheck(value.id, event.target.checked)}
        key={`check-${value.id}`}
      />
      <input
        type="text"
        value={value.name}
        data-automation-id="custom_field_value_settings_value"
        onChange={(event) => onUpdate(value.id, event.target.value)}
      />
      {index !== 0 && (
        <Dashicon
          icon="no-alt"
          color="#900"
          className="mailpoet-form-segments-segment-remove"
          onClick={partial(remove, value.id)}
        />
      )}
    </div>
  );
}

PreviewItem.propTypes = {
  value: PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
  }).isRequired,
  onUpdate: PropTypes.func.isRequired,
  onCheck: PropTypes.func.isRequired,
  index: PropTypes.number.isRequired,
  remove: PropTypes.func.isRequired,
};

function Preview({ values, update, remove, onReorder }) {
  const [valuesWhileMoved, setValues] = useState(values);

  useEffect(() => {
    setValues(values);
  }, [values]);

  if (valuesWhileMoved.length === 0) {
    return null;
  }

  const onUpdate = (valueId, text) => {
    const value = valuesWhileMoved.find((v) => v.id === valueId);
    value.name = text;
    update(value);
  };

  const onCheck = (valueId, checked) => {
    const value = valuesWhileMoved.find((v) => v.id === valueId);
    if (checked) {
      const checkedValue = valuesWhileMoved.find((v) => v.isChecked);
      if (checkedValue) {
        delete checkedValue.isChecked;
        update(checkedValue);
      }
      value.isChecked = true;
    } else {
      delete value.isChecked;
    }
    update(value);
  };

  const onDragEnd = (result) => {
    const from = result.source.index;
    const to = result.destination.index;
    const newValues = [...valuesWhileMoved];
    const [movedItem] = newValues.splice(from, 1);
    newValues.splice(to, 0, movedItem);
    setValues(newValues);
    onReorder(newValues);
  };

  const renderItems = () =>
    valuesWhileMoved.map((value, index) => (
      <Draggable key={value.id} draggableId={value.id} index={index}>
        {(provided) => (
          <div
            ref={provided.innerRef}
            {...provided.draggableProps}
            {...provided.dragHandleProps}
          >
            <PreviewItem
              key={`inner${value.id}`}
              index={index}
              value={value}
              remove={remove}
              onCheck={onCheck}
              onUpdate={onUpdate}
            />
          </div>
        )}
      </Draggable>
    ));

  return (
    <div className="mailpoet-dnd-items-list">
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
    </div>
  );
}

Preview.propTypes = {
  values: PropTypes.arrayOf(
    PropTypes.shape({
      name: PropTypes.string.isRequired,
      id: PropTypes.string.isRequired,
    }).isRequired,
  ).isRequired,
  update: PropTypes.func.isRequired,
  remove: PropTypes.func.isRequired,
  onReorder: PropTypes.func.isRequired,
};

export default Preview;
