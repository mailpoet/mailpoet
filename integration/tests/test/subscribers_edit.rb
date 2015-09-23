require_relative '../_init'

describe 'subscribers edit' do
  before do
    Admin::login
    click_on('Subscribers')

    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: 'test@mailpoet.com')
      fill_in('Firstname', with: 'Test')
      fill_in('Lastname', with: 'Last')
      select('Unconfirmed', from: 'field_status')
      click_on 'Save'
    end
    click_on('Subscribers')
  end

  it 'can edit a subscriber' do
    find('#subscribers a', text: 'test@mailpoet.com').hover
    find('#subscribers a', text: 'Edit').click

    page.must_have_field('E-mail', with: 'test@mailpoet.com')
    fill_in('Firstname', with: 'First')
    select('Unsubscribed', from: 'field_status')
    execute_script("$('#field_status').trigger('change'");

    click_on 'Save'

      page.must_have_content 'Subscriber succesfully updated!'
    within '#subscribers tbody' do
      page.must_have_content 'Unsubscribed'
    end
  end

  after do
    click_on('Subscribers')

    find('a', text: 'test@mailpoet.com').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
