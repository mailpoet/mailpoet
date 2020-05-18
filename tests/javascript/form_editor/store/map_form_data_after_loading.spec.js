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

    it('Sets default paddings', () => {
      expect(map(data).settings).to.have.property('formPadding', 20);
      expect(map(data).settings).to.have.property('inputPadding', 5);
    });

    it('Maps form and input padding', () => {
      const mapData = {
        ...data,
        settings: {
          ...data.settings,
          form_padding: 50,
          input_padding: 20,
        },
      };
      expect(map(mapData).settings).to.have.property('formPadding', 50);
      expect(map(mapData).settings).to.have.property('inputPadding', 20);
    });

    it('Sets default placements styles', () => {
      expect(map(data).settings).to.have.property('belowPostStyles').that.deep.eq({ width: { unit: 'percent', value: 100 } });
      expect(map(data).settings).to.have.property('popupStyles').that.deep.eq({ width: { unit: 'pixel', value: 560 } });
      expect(map(data).settings).to.have.property('fixedBarStyles').that.deep.eq({ width: { unit: 'percent', value: 100 } });
      expect(map(data).settings).to.have.property('slideInStyles').that.deep.eq({ width: { unit: 'pixel', value: 560 } });
      expect(map(data).settings).to.have.property('otherStyles').that.deep.eq({ width: { unit: 'percent', value: 100 } });
    });

    it('Keeps set placement styles', () => {
      const mapData = {
        ...data,
        settings: {
          ...data.settings,
          below_post_styles: { width: { unit: 'percent', value: 101 } },
          popup_styles: { width: { unit: 'percent', value: 102 } },
          fixed_bar_styles: { width: { unit: 'percent', value: 103 } },
          slide_in_styles: { width: { unit: 'percent', value: 104 } },
          other_styles: { width: { unit: 'percent', value: 105 } },
        },
      };
      expect(map(mapData).settings).to.have.property('belowPostStyles').that.deep.eq({ width: { unit: 'percent', value: 101 } });
      expect(map(mapData).settings).to.have.property('popupStyles').that.deep.eq({ width: { unit: 'percent', value: 102 } });
      expect(map(mapData).settings).to.have.property('fixedBarStyles').that.deep.eq({ width: { unit: 'percent', value: 103 } });
      expect(map(mapData).settings).to.have.property('slideInStyles').that.deep.eq({ width: { unit: 'percent', value: 104 } });
      expect(map(mapData).settings).to.have.property('otherStyles').that.deep.eq({ width: { unit: 'percent', value: 105 } });
    });
  });
});
