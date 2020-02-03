/* eslint-disable react/react-in-jsx-scope */
import PropTypes from 'prop-types';

const allForms = window.mailpoet_forms;

function Edit({ attributes, setAttributes }) {
  function displayFormsSelect() {
    if (!Array.isArray(allForms)) return null;
    if (allForms.length === 0) return null;
    return (
      <select
        onChange={(event) => {
          setAttributes({
            selectedForm: parseInt(event.target.value, 10),
          });
        }}
        defaultValue={attributes.selectedForm}
      >
        <option value="" disabled selected>Select a MailPoet form</option>
        {allForms.map((form) => (
          <option value={form.id}>
            {form.name}
          </option>
        ))}
      </select>
    );
  }

  return (
    <div className="mailpoet-block-div">
      <h2>MailPoet Subscription Form</h2>
      {displayFormsSelect()}
    </div>
  );
}

Edit.propTypes = {
  attributes: PropTypes.shape({
    selectedForm: PropTypes.number,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default Edit;
