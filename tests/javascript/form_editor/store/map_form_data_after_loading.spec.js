import { expect } from 'chai';
import map from '../../../../assets/js/src/form_editor/store/map_form_data_after_loading.jsx';

const data = {
  id: '1',
  name: 'My First Form',
  settings: {
    segments: ['3'],
    on_success: 'message',
    success_message: 'Check your inbox or spam folder to confirm your subscription.',
    success_page: '5',
    segments_selected_by: 'admin',
    place_form_bellow_all_pages: '1',
    place_form_bellow_all_posts: '',
  },
  styles: 'styles definition',
  created_at: '2020-01-15 07:39:15',
  updated_at: '2020-01-28 10:28:02',
  deleted_at: null,
};


describe('Form Data Load Mapper', () => {
  it('Returns ID', () => {
    expect(map(data)).to.have.property('id', '1');
  });

  it('Returns name', () => {
    expect(map(data)).to.have.property('name', 'My First Form');
  });

  it('Returns styles', () => {
    expect(map(data)).to.have.property('styles', 'styles definition');
  });

  it('Returns dates', () => {
    expect(map(data)).to.have.property('created_at', '2020-01-15 07:39:15');
    expect(map(data)).to.have.property('updated_at', '2020-01-28 10:28:02');
    expect(map(data)).to.have.property('deleted_at').that.is.null;
  });

  describe('Settings', () => {
    it('Maps settings', () => {
      expect(map(data)).to.have.property('settings').that.is.an('object');
    });

    it('Maps segments', () => {
      expect(map(data).settings).to.have.property('segments').that.deep.eq(['3']);
    });

    it('Maps Success', () => {
      expect(map(data).settings).to.have.property('on_success', 'message');
      expect(map(data).settings).to.have.property('success_message', 'Check your inbox or spam folder to confirm your subscription.');
      expect(map(data).settings).to.have.property('success_page', '5');
    });

    it('Maps placement', () => {
      expect(map(data).settings).to.have.property('placeFormBellowAllPages', true);
      expect(map(data).settings).to.have.property('placeFormBellowAllPosts', false);
    });

    it('Sets default padding', () => {
      expect(map(data).settings).to.have.property('formPadding', 10);
    });
  });
});
