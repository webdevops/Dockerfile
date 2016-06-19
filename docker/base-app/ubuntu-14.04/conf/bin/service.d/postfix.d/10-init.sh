# force new copy of hosts there (otherwise links could be outdated)
mkdir -p /var/spool/postfix/etc
cp -f /etc/hosts       /var/spool/postfix/etc/hosts
cp -f /etc/resolv.conf /var/spool/postfix/etc/resolv.conf
cp -f /etc/services    /var/spool/postfix/etc/services

if [[ -n "${POSTFIX_RELAYHOST+x}" ]]; else
    echo "relayhost = $POSTFIX_RELAYHOST" >> /etc/postfix/main.cf
fi

if [[ -n "${POSTFIX_MYNETWORKS+x}" ]]; else
    echo "mynetworks = $POSTFIX_MYNETWORKS" >> /etc/postfix/main.cf
fi
