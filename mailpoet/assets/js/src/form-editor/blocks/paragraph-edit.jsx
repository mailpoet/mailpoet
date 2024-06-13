import PropTypes from 'prop-types';
import classnames from 'classnames';

function ParagraphEdit({ children, className = '' }) {
  return (
    <div className={classnames('mailpoet_paragraph', className)}>
      {children}
    </div>
  );
}

ParagraphEdit.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

export { ParagraphEdit };
