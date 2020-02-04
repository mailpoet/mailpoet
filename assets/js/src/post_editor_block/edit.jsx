/* eslint-disable react/react-in-jsx-scope */
import PropTypes from 'prop-types';
import Icon from './icon.jsx';

const wp = window.wp;
const { Placeholder, PanelBody } = wp.components;
const { BlockIcon, InspectorControls } = wp.blockEditor;
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
        className="mailpoet-block-create-forms-list"
        value={attributes.selectedForm}
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

  function selectFormSettings() {
    return (
      <div className="mailpoet-block-create-new-content">
        <a
          href="admin.php?page=mailpoet-forms"
          target="_blank"
          className="mailpoet-block-create-new-link"
        >
          Create a new form
        </a>
        {displayFormsSelect()}
      </div>
    );
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title="MailPoet Subscription Form" initialOpen>
          {selectFormSettings()}
        </PanelBody>
      </InspectorControls>
      <div className="mailpoet-block-div">
        {
          attributes.selectedForm === null && (
            <Placeholder
              className="mailpoet-block-create-new"
              icon={<BlockIcon icon={Icon} showColors />}
              label="MailPoet Subscription Form"
            >
              {selectFormSettings()}
            </Placeholder>
          )
        }
        {
          attributes.selectedForm !== null && (
            renderForm()
          )
        }
      </div>
    </>
  );
}

Edit.propTypes = {
  attributes: PropTypes.shape({
    selectedForm: PropTypes.number,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default Edit;
