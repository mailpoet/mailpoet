import React, {
  useState,
  useEffect,
} from 'react';
import PropTypes from 'prop-types';
import { Dashicon } from '@wordpress/components';
import { partial } from 'lodash';
import { ReactSortable } from 'react-sortablejs';

const PreviewItem = ({
  value,
  remove,
  onUpdate,
  onCheck,
}) => (
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
    <Dashicon
      icon="no-alt"
      color="#900"
      className="mailpoet-form-segments-segment-remove"
      onClick={partial(remove, value.id)}
    />
  </div>
);

PreviewItem.propTypes = {
  value: PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
    isChecked: PropTypes.bool,
  }).isRequired,
  onUpdate: PropTypes.func.isRequired,
  onCheck: PropTypes.func.isRequired,
  remove: PropTypes.func.isRequired,
};

const Preview = ({
  values,
  update,
  remove,
  onReorder,
  useDragAndDrop,
}) => {
  const [valuesWhileMoved, setValues] = useState(values);

  useEffect(() => {
    setValues(values);
  }, [values]);

  if (valuesWhileMoved.length === 0) {
    return null;
  }

  // Remove unwanted properties added by sortable
  const sanitizeValue = (value) => {
    const sanitized = { ...value };
    delete sanitized.chosen;
    delete sanitized.selected;
    return sanitized;
  };

  const onUpdate = (valueId, text) => {
    const value = valuesWhileMoved.find((v) => v.id === valueId);
    value.name = text;
    update(sanitizeValue(value));
  };

  const onCheck = (valueId, checked) => {
    const value = valuesWhileMoved.find((v) => v.id === valueId);
    if (checked) {
      const checkedValue = valuesWhileMoved.find((v) => v.isChecked);
      if (checkedValue) {
        delete checkedValue.isChecked;
        update(sanitizeValue(checkedValue));
      }
      value.isChecked = true;
    } else {
      delete value.isChecked;
    }
    update(sanitizeValue(value));
  };

  const renderItems = () => (valuesWhileMoved.map((value, index) => (
    <PreviewItem
      key={value.id}
      index={index}
      value={value}
      remove={remove}
      onCheck={onCheck}
      onUpdate={onUpdate}
    />
  )));

  return (useDragAndDrop ? (
    <ReactSortable
      list={valuesWhileMoved}
      setList={(reorderedValues) => onReorder(reorderedValues.map(sanitizeValue))}
      className="mailpoet-dnd-items-list"
      animation={100}
    >
      {renderItems()}
    </ReactSortable>
  ) : (
    <div className="mailpoet-dnd-items-list">
      {renderItems()}
    </div>
  ));
};

Preview.propTypes = {
  values: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
  }).isRequired).isRequired,
  update: PropTypes.func.isRequired,
  remove: PropTypes.func.isRequired,
  onReorder: PropTypes.func.isRequired,
  useDragAndDrop: PropTypes.bool,
};

Preview.defaultProps = {
  useDragAndDrop: true,
};

export default Preview;
