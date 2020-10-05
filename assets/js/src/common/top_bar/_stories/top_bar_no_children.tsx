import React from 'react';
import { action } from '_storybook/action';
import { TopBar } from '../top_bar';

export default {
  title: 'Top Bar/No Children',
};

export const TopBarWithoutChildren = () => (
  <>
    <div style={{
      backgroundColor: '#bbb',
      width: '100%',
      position: 'fixed',
      height: '100%',
      top: '0px',
      left: '0px',
    }}
    >
      <TopBar
        hasNews={false}
        onBeamerClick={action('beamer click')}
        onLogoClick={action('logo click')}
      />
    </div>
  </>
);

export const TopBarWithoutChildrenWithNews = () => (
  <>
    <div style={{
      backgroundColor: '#bbb',
      width: '100%',
      position: 'fixed',
      height: '100%',
      top: '0px',
      left: '0px',
    }}
    >
      <TopBar
        hasNews
        onBeamerClick={action('beamer click')}
        onLogoClick={action('logo click')}
      />
    </div>
  </>
);
