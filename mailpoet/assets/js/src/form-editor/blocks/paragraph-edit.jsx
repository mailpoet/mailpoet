import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

function ParagraphEdit({ children, className = '' }) {
  const blockProps = useBlockProps();

  return (
    <div {...blockProps}>
      <div className={classnames('mailpoet_paragraph', className)}>
        {children}
      </div>
    </div>
  );
}

ParagraphEdit.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

export { ParagraphEdit };
