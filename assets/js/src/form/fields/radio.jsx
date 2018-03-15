define([
  'react',
],
(
  React
) => {
  const FormFieldRadio = React.createClass({
    render: function render() {
      if (this.props.field.values === undefined) {
        return false;
      }

      const selectedValue = this.props.item[this.props.field.name];
      const options = Object.keys(this.props.field.values).map(
        (value, index) => (
          <p key={`radio-${index}`}>
            <label htmlFor={this.props.field.name}>
              <input
                type="radio"
                checked={selectedValue === value}
                value={value}
                onChange={this.props.onValueChange}
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

  return FormFieldRadio;
});
