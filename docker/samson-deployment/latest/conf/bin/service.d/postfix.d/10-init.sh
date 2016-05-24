# force new copy of hosts there (otherwise links could be outdated)
mkdir -p /var/spool/postfix/etc
cp -f /etc/hosts       /var/spool/postfix/etc/hosts
cp -f /etc/resolv.conf /var/spool/postfix/etc/resolv.conf
cp -f /etc/services    /var/spool/postfix/etc/services
