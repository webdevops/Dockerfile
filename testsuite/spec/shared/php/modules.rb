shared_examples 'php::modules' do
    describe command('php -m') do
        its(:stdout) { should_not contain('xdebug') }
        its(:stdout) { should     contain('bcmath') }
        its(:stdout) { should     contain('bz2') }
        its(:stdout) { should     contain('calendar') }
        its(:stdout) { should     contain('Core') }
        its(:stdout) { should     contain('ctype') }
        its(:stdout) { should     contain('curl') }
        its(:stdout) { should     contain('date') }
        its(:stdout) { should     contain('dom') }
        its(:stdout) { should     contain('exif') }
        its(:stdout) { should     contain('fileinfo') }
        its(:stdout) { should     contain('filter') }
        its(:stdout) { should     contain('ftp') }
        its(:stdout) { should     contain('gd') }
        its(:stdout) { should     contain('gettext') }
        its(:stdout) { should     contain('hash') }
        its(:stdout) { should     contain('iconv') }
        its(:stdout) { should     contain('json') }
        its(:stdout) { should     contain('ldap') }
        its(:stdout) { should     contain('libxml') }
        its(:stdout) { should     contain('mbstring') }
        its(:stdout) { should     contain('mysqli') }
        its(:stdout) { should     contain('mysqlnd') }
        its(:stdout) { should     contain('openssl') }
        its(:stdout) { should     contain('pcntl') }
        its(:stdout) { should     contain('pcre') }
        its(:stdout) { should     contain('PDO') }
        its(:stdout) { should     contain('pdo_mysql') }
        its(:stdout) { should     contain('pdo_sqlite') }
        its(:stdout) { should     contain('Phar') }
        its(:stdout) { should     contain('posix') }
        its(:stdout) { should     contain('Reflection') }
        its(:stdout) { should     contain('session') }
        its(:stdout) { should     contain('SimpleXML') }
        its(:stdout) { should     contain('soap') }
        its(:stdout) { should     contain('sockets') }
        its(:stdout) { should     contain('SPL') }
        its(:stdout) { should     contain('sqlite3') }
        its(:stdout) { should     contain('standard') }
        its(:stdout) { should     contain('sysvmsg') }
        its(:stdout) { should     contain('sysvsem') }
        its(:stdout) { should     contain('sysvshm') }
        its(:stdout) { should     contain('tokenizer') }
        its(:stdout) { should     contain('xml') }
        its(:stdout) { should     contain('xmlreader') }
        its(:stdout) { should     contain('xmlrpc') }
        its(:stdout) { should     contain('xmlwriter') }
        its(:stdout) { should     contain('xsl') }
        its(:stdout) { should     contain('zip') }
        its(:stdout) { should     contain('zlib') }
    end
end

shared_examples 'php5::modules' do
    describe command('php -m') do
        its(:stdout) { should     contain('shmop') }
        its(:stdout) { should     contain('mhash') }
        its(:stdout) { should     contain('wddx') }
    end
end

shared_examples 'php7::modules' do
    describe command('php -m') do
        its(:stdout) { should     contain('Zend OPcache') }
    end
end
