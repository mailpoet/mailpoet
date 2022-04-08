import { useMemo } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import ParagraphEdit from '../paragraph_edit.jsx';
import Settings from './settings.jsx';

function SegmentSelectEdit({ attributes, setAttributes }) {
  const segments = useSelect(
    (sel) => sel('mailpoet-form-editor').getAllAvailableSegments(),
    [],
  );
  const valuesWithNames = useMemo(
    () =>
      attributes.values.map((value) => {
        const valueWithName = { ...value };
        const segment = segments.find(
          (seg) => parseInt(seg.id, 10) === parseInt(value.id, 10),
        );
        valueWithName.name = segment ? segment.name : '';
        return valueWithName;
      }),
    [attributes.values, segments],
  );
  const stripNamesFromValues = (values) =>
    values.map((value) => {
      const valueWithoutName = { ...value };
      delete valueWithoutName.name;
      return valueWithoutName;
    });
  const renderValues = () => {
    if (attributes.values.length === 0) {
      return (
        <p className="mailpoet_error">
          {MailPoet.I18n.t('blockSegmentSelectNoLists')}
        </p>
      );
    }
    return valuesWithNames.map((value) => (
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
        onLabelChanged={(label) => setAttributes({ label })}
        segmentsAddedIntoSelection={valuesWithNames}
        setNewSelection={(selection) =>
          setAttributes({ values: stripNamesFromValues(selection) })
        }
        addSegmentIntoSelection={(newSegment) =>
          setAttributes({
            values: stripNamesFromValues([...attributes.values, newSegment]),
          })
        }
      />
      <span
        className="mailpoet_segment_label"
        data-automation-id="mailpoet_list_selection_block"
      >
        {attributes.label}
      </span>
      {renderValues()}
    </ParagraphEdit>
  );
}

SegmentSelectEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    className: PropTypes.string,
    values: PropTypes.arrayOf(
      PropTypes.shape({
        isChecked: PropTypes.bool,
        id: PropTypes.string.isRequired,
      }),
    ).isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SegmentSelectEdit;
