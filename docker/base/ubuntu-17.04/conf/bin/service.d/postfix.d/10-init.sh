# force new copy of hosts there (otherwise links could be outdated)
mkdir -p /var/spool/postfix/etc
cp -f /etc/hosts       /var/spool/postfix/etc/hosts
cp -f /etc/resolv.conf /var/spool/postfix/etc/resolv.conf
cp -f /etc/services    /var/spool/postfix/etc/services

## MYHOSTNAME
# replace line
sed -i '/myhostname[ ]* =/c\' main.cf
echo "myhostname = $(hostname)" >> /etc/postfix/main.cf

## REPLAYHOST
if [[ -n "${POSTFIX_RELAYHOST+x}" ]]; then
    # replace line
    sed -i '/relayhost[ ]* =/c\' main.cf
    echo "relayhost = $POSTFIX_RELAYHOST" >> /etc/postfix/main.cf
fi

## MYNETWORKS
if [[ -n "${POSTFIX_MYNETWORKS+x}" ]]; then
    # replace line
    sed -i '/mynetworks[ ]* =/c\' main.cf
    echo "mynetworks = $POSTFIX_MYNETWORKS" >> /etc/postfix/main.cf
fi

# generate aliases db
newaliases || :
