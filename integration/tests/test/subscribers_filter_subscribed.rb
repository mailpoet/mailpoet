require_relative '../_init'

describe 'subscribers filter by subscribed' do
  before do
    Admin::login
    click_on('Subscribers')

    page.must_have_content 'New'
    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: "test@mailpoet.com")
      fill_in('Firstname', with: "First")
      fill_in('Lastname', with: "Last")
      click_on 'Save'
    end
    click_on('Subscribers')

    page.must_have_content 'New'
    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: "test_subscribed@mailpoet.com")
      fill_in('Firstname', with: "First")
      fill_in('Lastname', with: "Last")
      select('Subscribed', from: 'field_status')
      click_on 'Save'
    end
    click_on('Subscribers')
  end

  it 'has the correct initial counts' do
    page.must_have_content '2 item(s)'
    page.must_have_content 'All (2)'
    page.must_have_content 'Subscribed (1)'
    page.must_have_content 'Unsubscribed (1)'
  end

  it 'can filter subscribers by subscribed' do
    page.must_have_content '2 item(s)'

    click_link('Subscribed')
    sleep 1

    page.must_have_content '1 item(s)'

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      'test_subscribed@mailpoet.com'
    ]

    page_order.must_equal correct_order
  end

  after do
    page.must_have_content "test@mailpoet.com"
    find('a', text: "test@mailpoet.com").hover
    find('a', text: 'Trash').click

    page.must_have_content "test_subscribed@mailpoet.com"
    find('a', text: "test_subscribed@mailpoet.com").hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
