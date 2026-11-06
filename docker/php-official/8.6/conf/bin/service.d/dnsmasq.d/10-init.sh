# Create dnsmasq.d directory if not exists
mkdir -p -- /etc/dnsmasq.d/

# Enable /etc/dnsmasq.d/
go-replace --mode=lineinfile --once \
    -s 'conf-dir' -r 'conf-dir=/etc/dnsmasq.d/,*.conf' \
    -- /etc/dnsmasq.conf

## clear dns file
echo > /etc/dnsmasq.d/webdevops.conf

if [ ! -f /etc/resolv.conf.original ]; then
    cp -a /etc/resolv.conf /etc/resolv.conf.original

    ## set forward servers
    cat /etc/resolv.conf.original | grep nameserver | sed 's/nameserver /server=/' > /etc/dnsmasq.d/forward.conf

    ## set dnsmasq to main nameserver
    echo "nameserver 127.0.0.1" > /etc/resolv.conf
fi


# Add own VIRTUAL_HOST as loopback
if [[ -n "${VIRTUAL_HOST+x}" ]]; then
    # split comma by space
    VIRTUAL_HOST_LIST=${VIRTUAL_HOST//,/$'\n'}

    # replace *.domain for dns specific .domain wildcard
    VIRTUAL_HOST_LIST=${VIRTUAL_HOST_LIST/\*./.}

    # no support for .*
    VIRTUAL_HOST_LIST=${VIRTUAL_HOST_LIST/.\*/.}

    for DOMAIN in $VIRTUAL_HOST_LIST; do
        echo "address=/${DOMAIN}/127.0.0.1" >>  /etc/dnsmasq.d/webdevops.conf
    done
fi
