require_relative '../_init'

describe 'subscribers order by email' do
  before do
    Admin::login
    click_on('Subscribers')

    3.times do |n|
      page.must_have_content 'New'
      within '#subscribers' do
        click_on 'New'
        fill_in('E-mail', with: "test#{n}@mailpoet.com")
        fill_in('Firstname', with: "First #{n}")
        fill_in('Lastname', with: "Last #{n}")
        select('Unconfirmed', from: 'field_status')
        click_on 'Save'
      end
      click_on('Subscribers')
    end
  end

  it 'shows all subscribers ordered by creation' do
    page.must_have_content 'test2@mailpoet.com'
    page.must_have_content 'test1@mailpoet.com'
    page.must_have_content 'test0@mailpoet.com'

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      'test2@mailpoet.com',
      'test1@mailpoet.com',
      'test0@mailpoet.com'
    ]

    page_order.must_equal correct_order
  end

  it 'can order subscribers by asc or desc subject' do
    page.must_have_content 'test2@mailpoet.com'
    page.must_have_content 'test1@mailpoet.com'
    page.must_have_content 'test0@mailpoet.com'

    first('a', text: 'Email').click
    sleep 1

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      'test0@mailpoet.com',
      'test1@mailpoet.com',
      'test2@mailpoet.com'
    ]

    page_order.must_equal correct_order

    first('a', text: 'Email').click
    sleep 1

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      'test2@mailpoet.com',
      'test1@mailpoet.com',
      'test0@mailpoet.com'
    ]

    page_order.must_equal correct_order
  end

  after do
    3.times do |n|
      page.must_have_content "test#{n}@mailpoet.com"
      find('a', text: "test#{n}@mailpoet.com").hover
      find('a', text: 'Trash').click
    end
    Admin::logout
  end
end
