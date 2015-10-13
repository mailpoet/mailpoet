require_relative '../_init'

describe 'subscribers filter by subscribed' do
  before do
    Admin::login
    click_on('Subscribers')

    page.must_have_content 'All (0)'
    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: "test@mailpoet.com")
      fill_in('Firstname', with: "First")
      fill_in('Lastname', with: "Last")
      click_on 'Save'
    end

    page.must_have_content 'All (1)'
    within '#subscribers' do
      click_on 'New'
      fill_in('E-mail', with: "test_subscribed@mailpoet.com")
      fill_in('Firstname', with: "First")
      fill_in('Lastname', with: "Last")
      select('Unsubscribed', from: 'field_status')
      execute_script("$('#field_status').trigger('change'");
      click_on 'Save'
    end
  end

  it 'has the correct initial counts' do
    page.must_have_content '2 item(s)'
    page.must_have_content 'All (2)'
    page.must_have_content 'Unconfirmed (1)'
    page.must_have_content 'Unsubscribed (1)'
  end

  # it 'can filter subscribers by subscribed' do
  #   page.must_have_content '2 item(s)'

  #   within '.subsubsub' do
  #     click_link('Subscribed')
  #     sleep 1
  #   end

  #   page.must_have_content '1 item(s)'

  #   page_order = all('table tbody tr').map do |row|
  #     row.first('a').text
  #   end

  #   correct_order = [
  #     'test_subscribed@mailpoet.com'
  #   ]

  #   page_order.must_equal correct_order
  # end

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
