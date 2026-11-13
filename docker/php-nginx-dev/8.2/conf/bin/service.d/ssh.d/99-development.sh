
# Allow root access via ssh
go-replace --mode=lineinfile -s 'PermitRootLogin' -r 'PermitRootLogin yes' -- /etc/ssh/sshd_config
