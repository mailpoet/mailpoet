require_relative '../_init'

describe 'subscribers new' do
  before do
    Admin::login
    click_on('Subscribers')
  end

  it 'can create a subscriber' do
    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: 'test@mailpoet.com')
      fill_in('Firstname', with: 'Test')
      fill_in('Lastname', with: 'Last')
      select('Unconfirmed', from: 'field_status')
      click_on 'Save'
    end

    page.must_have_content 'Subscriber succesfully added!'
    page.must_have_content 'test@mailpoet.com'
    page.must_have_content 'Test'
    page.must_have_content 'Last'
    page.must_have_content 'Unconfirmed'
  end

  after do
    click_on('Subscribers')

    find('a', text: 'test@mailpoet.com').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
