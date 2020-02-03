/* eslint-disable react/react-in-jsx-scope */
import PropTypes from 'prop-types';
import Icon from './icon.jsx';

const wp = window.wp;
const { Placeholder } = wp.components;
const { BlockIcon } = wp.blockEditor;
const ServerSideRender = wp.serverSideRender;
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

  function renderForm() {
    return (
      <ServerSideRender
        block="mailpoet/form-block-render"
        attributes={{ form: attributes.selectedForm }}
      />
    );
  }

  return (
    <div className="mailpoet-block-div">
      {
        attributes.selectedForm === null && (
          <Placeholder
            className="mailpoet-block-div"
            icon={<BlockIcon icon={Icon} showColors />}
            label="MailPoet Subscription Form"
          >
            {displayFormsSelect()}
          </Placeholder>
        )
      }
      {
        attributes.selectedForm !== null && (
          renderForm()
        )
      }
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
