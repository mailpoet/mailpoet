require_relative '../_init'

describe 'subscribers blank list' do
  before do
    Admin::login
    click_on('Subscribers')
  end

  it 'has a title' do
    page.must_have_content 'Subscribers'
  end

  it 'has a new button' do
    page.must_have_content 'New'
  end

  it 'has an zeroed all counter' do
    page.must_have_content 'All (0)'
  end

  it 'has a zeroed subscribed counter' do
    page.must_have_content 'Subscribed (0)'
  end

  it 'has a zeroed unconfirmed counter' do
    page.must_have_content 'Unconfirmed (0)'
  end

  it 'has a zeroed unsubscribed counter' do
    page.must_have_content 'Unsubscribed (0)'
  end

  it 'has a search form' do
    page.must_have_content 'Search'
  end

  it 'has bulk actions' do
    page.must_have_content 'Select bulk action'
  end

  it 'has the column titles' do
    page.must_have_content 'Email'
    page.must_have_content 'Firstname'
    page.must_have_content 'Lastname'
    page.must_have_content 'Status'
    page.must_have_content 'Subscribed on'
    page.must_have_content 'Last modified on'
  end

  it 'shows a message if there are no newsletters' do
    page.must_have_content 'No subscribers found'
  end

  after do
    Admin::logout
  end
end
