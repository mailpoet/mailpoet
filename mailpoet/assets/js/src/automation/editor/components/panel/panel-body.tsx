import { PanelBody as WpPanelBody } from '@wordpress/components';
import { useEffect, useState } from 'react';

type Props = WpPanelBody.Props & {
  hasErrors?: boolean;
};

export function PanelBody({ hasErrors = false, ...props }: Props): JSX.Element {
  const [isOpened, setIsOpened] = useState(props.initialOpen);

  useEffect(() => {
    if (hasErrors) {
      setIsOpened(true);
    }
  }, [hasErrors]);

  return (
    <WpPanelBody
      opened={isOpened}
      onToggle={() => setIsOpened((prevState) => !prevState)}
      {...props}
    />
  );
}
