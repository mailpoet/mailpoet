require_relative '../_init'

describe 'Welcome Page' do
  before do
    Admin::login
  end

  it 'has a title' do
    page.must_have_content 'Welcome!'
  end

  after do
    Admin::logout
  end
end
