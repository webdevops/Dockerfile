#!/bin/bash

echo "(Re-)Starting Samson"
cd /app/
bin/rake db:migrate RAILS_ENV=development
bundle exec puma -C ./config/puma.rb