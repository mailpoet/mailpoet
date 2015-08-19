define('settings', ['react'], function(React) {

  var STATES = [
    'AL', 'AK', 'AS', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI',
    'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS',
    'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR',
    'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
  ]

  var Example = React.createClass({
    getInitialState: function() {
      return {
        email: true
      , question: true
      , submitted: null
      }
    },
    render: function() {
      var submitted
      if (this.state.submitted !== null) {
        submitted = <div className="alert alert-success">
          <p>ContactForm data:</p>
          <pre><code>{JSON.stringify(this.state.submitted, null, '  ')}</code></pre>
        </div>
      }

      return <div>
        <div className="panel panel-default">
          <div className="panel-heading clearfix">
            <h3 className="panel-title pull-left">Contact Form</h3>
            <div className="pull-right">
              <label className="checkbox-inline">
                <input type="checkbox"
                  checked={this.state.email}
                  onChange={this.handleChange.bind(this, 'email')}
                /> Email
              </label>
              <label className="checkbox-inline">
                <input type="checkbox"
                  checked={this.state.question}
                  onChange={this.handleChange.bind(this, 'question')}
                /> Question
              </label>
            </div>
          </div>
          <div className="panel-body">
            <ContactForm ref="contactForm"
              email={this.state.email}
              question={this.state.question}
              company={this.props.company}
            />
          </div>
          <div className="panel-footer">
            <button type="button" className="btn btn-primary btn-block" onClick={this.handleSubmit}>Submit</button>
          </div>
        </div>
        {submitted}
      </div>
    },
    handleChange: function(field, e) {
      var nextState = {}
      nextState[field] = e.target.checked
      this.setState(nextState)
    },
    handleSubmit: function() {
      if (this.refs.contactForm.isValid()) {
        this.setState({submitted: this.refs.contactForm.getFormData()})
      }
    }
  })

  /**
   * A contact form with certain optional fields.
   */
  var ContactForm = React.createClass({
    getDefaultProps: function() {
      return {
        email: true
      , question: false
      }
    },
    getInitialState: function() {
      return {errors: {}}
    },
    isValid: function() {
      var fields = ['firstName', 'lastName', 'phoneNumber', 'address', 'city', 'state', 'zipCode']
      if (this.props.email) fields.push('email')
      if (this.props.question) fields.push('question')

      var errors = {}
      fields.forEach(function(field) {
        var value = trim(this.refs[field].getDOMNode().value)
        if (!value) {
          errors[field] = 'This field is required'
        }
      }.bind(this))
      this.setState({errors: errors})

      var isValid = true
      for (var error in errors) {
        isValid = false
        break
      }
      return isValid
    },
    getFormData: function() {
      var data = {
        firstName: this.refs.firstName.getDOMNode().value
      , lastName: this.refs.lastName.getDOMNode().value
      , phoneNumber: this.refs.phoneNumber.getDOMNode().value
      , address: this.refs.address.getDOMNode().value
      , city: this.refs.city.getDOMNode().value
      , state: this.refs.state.getDOMNode().value
      , zipCode: this.refs.zipCode.getDOMNode().value
      , currentCustomer: this.refs.currentCustomerYes.getDOMNode().checked
      }
      if (this.props.email) data.email = this.refs.email.getDOMNode().value
      if (this.props.question) data.question = this.refs.question.getDOMNode().value
      return data
    },
    render: function() {
      return <div className="form-horizontal">
        {this.renderTextInput('firstName', 'First Name')}
        {this.renderTextInput('lastName', 'Last Name')}
        {this.renderTextInput('phoneNumber', 'Phone number')}
        {this.props.email && this.renderTextInput('email', 'Email')}
        {this.props.question && this.renderTextarea('question', 'Question')}
        {this.renderTextInput('address', 'Address')}
        {this.renderTextInput('city', 'City')}
        {this.renderSelect('state', 'State', STATES)}
        {this.renderTextInput('zipCode', 'Zip Code')}
        {this.renderRadioInlines('currentCustomer', 'Are you currently a ' + this.props.company + ' Customer?', {
          values: ['Yes', 'No']
        , defaultCheckedValue: 'No'
        })}
      </div>
    },
    renderTextInput: function(id, label) {
      return this.renderField(id, label,
        <input type="text" className="form-control" id={id} ref={id}/>
      )
    },
    renderTextarea: function(id, label) {
      return this.renderField(id, label,
        <textarea className="form-control" id={id} ref={id}/>
      )
    },
    renderSelect: function(id, label, values) {
      var options = values.map(function(value) {
        return <option value={value}>{value}</option>
      })
      return this.renderField(id, label,
        <select className="form-control" id={id} ref={id}>
          {options}
        </select>
      )
    },
    renderRadioInlines: function(id, label, kwargs) {
      var radios = kwargs.values.map(function(value) {
        var defaultChecked = (value == kwargs.defaultCheckedValue)
        return <label className="radio-inline">
          <input type="radio" ref={id + value} name={id} value={value} defaultChecked={defaultChecked}/>
          {value}
        </label>
      })
      return this.renderField(id, label, radios)
    },
    renderField: function(id, label, field) {
      return <div className={$c('form-group', {'has-error': id in this.state.errors})}>
        <label htmlFor={id} className="col-sm-4 control-label">{label}</label>
        <div className="col-sm-6">
          {field}
        </div>
      </div>
    }
  })

  React.render(<Example company="FakeCo"/>, document.getElementById('mailpoet_settings'))

  // Utils
  var trim = function() {
    var TRIM_RE = /^\s+|\s+$/g
    return function trim(string) {
      return string.replace(TRIM_RE, '')
    }
  }()

  function $c(staticClassName, conditionalClassNames) {
    var classNames = []
    if (typeof conditionalClassNames == 'undefined') {
      conditionalClassNames = staticClassName
    }
    else {
      classNames.push(staticClassName)
    }
    for (var className in conditionalClassNames) {
      if (!!conditionalClassNames[className]) {
        classNames.push(className)
      }
    }
    return classNames.join(' ')
  }
});