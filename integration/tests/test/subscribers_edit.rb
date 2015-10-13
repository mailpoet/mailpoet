require_relative '../_init'

describe 'subscribers edit' do
  before do
    Admin::login
    click_on('Subscribers')

    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: 'test@mailpoet.com')
      select('Subscribed', from: 'field_status')
      fill_in('Firstname', with: 'Test')
      fill_in('Lastname', with: 'Last')
      click_on 'Save'
    end
  end

  it 'can edit a subscriber' do
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
