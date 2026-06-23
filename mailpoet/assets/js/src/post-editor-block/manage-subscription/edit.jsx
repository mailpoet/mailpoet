/* eslint-disable react/react-in-jsx-scope */
const wp = window.wp;
const { Disabled } = wp.components;
const { useBlockProps } = wp.blockEditor;
const ServerSideRender = wp.serverSideRender;

function Edit() {
  const blockProps = useBlockProps();

  return (
    <div {...blockProps}>
      <Disabled>
        <ServerSideRender block="mailpoet/manage-subscription-block-render" />
      </Disabled>
    </div>
  );
}

export { Edit };
