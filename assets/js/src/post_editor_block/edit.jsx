/* eslint-disable react/react-in-jsx-scope */
const wp = window.wp;
const { useState } = wp.element;

const allForms = window.mailpoet_forms;

function Edit() {
  function displayFormsSelect() {
    if (!Array.isArray(allForms)) return null;
    if (allForms.length === 0) return null;
    return (
      <select>
        <option value="" disabled selected>Select a MailPoet form</option>
        {allForms.map((form) => (
          <option value={form.id}>{form.name}</option>
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

export default Edit;
