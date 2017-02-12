base_spec_dir = Pathname.new(File.join(File.dirname(__FILE__)))

Dir[base_spec_dir.join('shared/**/*.rb')].sort.each{ |f| require f }
Dir[base_spec_dir.join('collection/**.rb')].sort.each{ |f| require f }
