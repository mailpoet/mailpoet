define([
  'react',
],
(
  React
) => {
  const FormFieldCheckbox = React.createClass({
    onValueChange: function onValueChange(e) {
      e.target.value = this.checkbox.checked ? '1' : '0';
      return this.props.onValueChange(e);
    },
    render: function render() {
      if (this.props.field.values === undefined) {
        return false;
      }

      // isChecked will be true only if the value is "1"
      // it will be false in case value is "0" or empty
      const isChecked = !!(Number(this.props.item[this.props.field.name]));
      const options = Object.keys(this.props.field.values).map(
        (value, index) => (
          <p key={`checkbox-${index}`}>
            <label htmlFor={this.props.field.name}>
              <input
                ref={(c) => { this.checkbox = c; }}
                type="checkbox"
                value="1"
                checked={isChecked}
                onChange={this.onValueChange}
                name={this.props.field.name}
                id={this.props.field.name}
              />
              { this.props.field.values[value] }
            </label>
          </p>
          )
      );

      return (
        <div>
          { options }
        </div>
      );
    },
  });

  return FormFieldCheckbox;
});
