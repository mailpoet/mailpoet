import React from 'react';
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
      <TopBar />
    </div>
  </>
);
