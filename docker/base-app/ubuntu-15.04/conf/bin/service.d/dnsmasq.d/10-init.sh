## clear dns file
echo > /etc/dnsmasq.d/webdevops

if [ ! -f /etc/resolv.conf.original ]; then
    cp -a /etc/resolv.conf /etc/resolv.conf.original

    ## set forward servers
    cat /etc/resolv.conf.original | grep nameserver | sed 's/nameserver /server=/' > /etc/dnsmasq.d/forward

    ## set dnsmasq to main nameserver
    echo "nameserver 127.0.0.1" > /etc/resolv.conf
fi

# Add own VIRTUAL_HOST as loopback
if [[ -n "${VIRTUAL_HOST+x}" ]]; then
    echo "address=/${VIRTUAL_HOST}/127.0.0.1" >>  /etc/dnsmasq.d/webdevops
fi
