import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import ParagraphEdit from '../paragraph_edit.jsx';
import Settings from './settings.jsx';

const SegmentSelectEdit = ({ attributes, setAttributes }) => {
  const renderValues = () => {
    if (attributes.values.length === 0) {
      return (<p className="mailpoet_error">{MailPoet.I18n.t('blockSegmentSelectNoLists')}</p>);
    }
    return attributes.values.map((value) => (
      <label key={value.id} className="mailpoet_checkbox_label">
        <input
          type="checkbox"
          disabled
          key={value.id}
          checked={!!value.isChecked}
          className="mailpoet_checkbox"
        />
        {value.name}
      </label>
    ));
  };

  return (
    <ParagraphEdit className={attributes.className}>
      <Settings
        label={attributes.label}
        onLabelChanged={(label) => (setAttributes({ label }))}
        segmentsAddedIntoSelection={attributes.values}
        setNewSelection={(selection) => setAttributes({ values: selection })}
        addSegmentIntoSelection={(newSegment) => setAttributes({
          values: [
            ...attributes.values,
            newSegment,
          ],
        })}
      />
      <span className="mailpoet_segment_label">
        {attributes.label}
      </span>
      {renderValues()}
    </ParagraphEdit>
  );
};

SegmentSelectEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    className: PropTypes.string,
    values: PropTypes.arrayOf(PropTypes.shape({
      isChecked: PropTypes.boolean,
      name: PropTypes.string.isRequired,
      id: PropTypes.string.isRequired,
    })).isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SegmentSelectEdit;
