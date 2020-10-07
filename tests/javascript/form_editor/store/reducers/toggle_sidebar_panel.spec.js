import { expect } from 'chai';
import reducer from '../../../../../assets/js/src/form_editor/store/reducers/toggle_sidebar_panel.jsx';

describe('Toggle Sidebar Panel Reducer', () => {
  let initialState = null;
  beforeEach(() => {
    initialState = {
      something: 'value',
      sidebar: {
        activeTab: 'form',
        openedPanels: [],
      },
    };
  });

  it('Should keep unrelated state properties untouched', () => {
    const action = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    const finalState = reducer(initialState, action);
    expect(finalState.something).to.equal('value');
    expect(finalState.sidebar.activeTab).to.equal('form');
  });

  it('Should open closed panel', () => {
    const action = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    const finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.include('nice-panel');
  });

  it('Should close opened panel', () => {
    const action = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    initialState.sidebar.openedPanels.push('nice-panel');
    const finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.not.include('nice-panel');
  });

  it('Should toggle panel to required state', () => {
    const action = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
      toggleTo: true,
    };
    reducer(initialState, action);
    let finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.include('nice-panel');
    action.toggleTo = false;
    finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.not.include('nice-panel');
  });
});
