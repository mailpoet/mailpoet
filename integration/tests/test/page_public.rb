require_relative '../_init'

describe 'Public Page' do
  it 'has a post' do
    visit '/'
    page.must_have_content 'Hello world!'
  end
end
