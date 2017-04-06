# Init ssh privilege separation directory
mkdir -p /var/run/sshd
chown root:root /var/run/sshd
chmod 755 /var/run/sshd

# generate host keys
ssh-keygen -A
