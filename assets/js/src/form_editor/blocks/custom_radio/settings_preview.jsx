import React, {
  useRef,
  useCallback,
  useState,
  useEffect,
} from 'react';
import PropTypes from 'prop-types';
import { Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import Backend from 'react-dnd-html5-backend';

const PreviewItem = ({
  value,
  index,
  moveItem,
  remove,
  onUpdate,
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
    item: { type: 'item', id: value.id, index },
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
      key={value.id}
      ref={ref}
      style={{ opacity }}
    >
      <input
        type="radio"
        onChange={(event) => onCheck(value.id, event.target.value)}
        key={`check-${value.id}`}
      />
      <input
        type="text"
        value={value.name}
        onChange={(event) => onUpdate(value.id, event.target.value)}
      />
      <Dashicon
        icon="no-alt"
        color="#900"
        className="mailpoet-form-segments-segment-remove"
        onClick={partial(remove, value.id)}
      />
    </div>
  );
};

PreviewItem.propTypes = {
  value: PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
  }).isRequired,
  onUpdate: PropTypes.func.isRequired,
  onCheck: PropTypes.func.isRequired,
  moveItem: PropTypes.func.isRequired,
  remove: PropTypes.func.isRequired,
  index: PropTypes.number.isRequired,
  dragFinished: PropTypes.func.isRequired,
};

const Preview = ({
  values,
  update,
  remove,
  onReorder,
}) => {
  const [valuesWhileMoved, setSegments] = useState(values);
  const moveItem = useCallback(
    (dragIndex, hoverIndex) => {
      const result = Array.from(valuesWhileMoved);
      const [removed] = result.splice(dragIndex, 1);
      result.splice(hoverIndex, 0, removed);

      setSegments(result);
    },
    [valuesWhileMoved, setSegments],
  );

  useEffect(() => {
    setSegments(values);
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
    value.isChecked = checked;
    update(value);
  };

  return (
    <DndProvider backend={Backend}>
      {valuesWhileMoved.map((value, index) => (
        <PreviewItem
          key={value.id}
          index={index}
          value={value}
          moveItem={moveItem}
          remove={remove}
          onCheck={onCheck}
          onUpdate={onUpdate}
          dragFinished={() => onReorder(valuesWhileMoved)}
        />
      ))}
    </DndProvider>
  );
};

Preview.propTypes = {
  values: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
  update: PropTypes.func.isRequired,
  remove: PropTypes.func.isRequired,
  onReorder: PropTypes.func.isRequired,
};

export default Preview;
