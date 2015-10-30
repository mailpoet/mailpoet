import React from 'react'
import ReactDOM from 'react-dom'
import { Router, History } from 'react-router'
import MailPoet from 'mailpoet'
import Form from 'form/form.jsx'

const fields = [
  {
    name: 'name',
    label: 'Name',
    type: 'text'
  },
  {
    name: 'segments',
    label: 'Lists',
    type: 'selection',
    endpoint: 'segments'
  }
]

const messages = {
  updated: function() {
    MailPoet.Notice.success('Form successfully updated!');
  },
  created: function() {
    MailPoet.Notice.success('Form successfully added!');
  }
}

const FormForm = React.createClass({
  mixins: [
    History
  ],
  render() {
    return (
      <div>
        <h2 className="title">
          Form <a
            href="javascript:;"
            className="add-new-h2"
            onClick={ this.history.goBack }
          >Back to list</a>
        </h2>

        <Form
          endpoint="forms"
          fields={ fields }
          params={ this.props.params }
          messages={ messages }
          onSuccess={ this.history.goBack } />
      </div>
    );
  }
});

module.exports = FormForm