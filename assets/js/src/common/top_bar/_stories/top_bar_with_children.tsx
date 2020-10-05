import React from 'react';
import { TopBar } from '../top_bar';
import Button from '../../button/button';

export default {
  title: 'Top Bar/With Children',
};

export const TopBarWithChildren = () => (
  <div style={{
    backgroundColor: '#bbb',
    width: '100%',
    position: 'fixed',
    height: '100%',
    top: '0px',
    left: '0px',
  }}
  >
    <TopBar>
      <Button>Button</Button>
    </TopBar>
  </div>
);
