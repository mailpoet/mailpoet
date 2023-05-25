import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { aiSidebarKey, storeName } from '../../store';

export function AI(): JSX.Element {
  const { openSidebar } = useDispatch(storeName);

  return (
    <div style={{ display: 'grid' }}>
      <Button
        onClick={() => void openSidebar(aiSidebarKey)}
        style={{
          border: '1px solid #1e1e1e',
          borderRadius: '99999px',
          background: '#1e1e1e',
          height: '26px',
          width: '26px',
          color: 'white',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          margin: 'auto',
        }}
        className="mailpoet-automation-editor-dropdown-toggle-link"
      >
        <span style={{ color: 'white' }}>AI</span>
      </Button>
    </div>
  );
}
