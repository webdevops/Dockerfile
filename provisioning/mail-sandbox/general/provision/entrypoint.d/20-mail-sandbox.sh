#!/usr/bin/env bash

if ! id -u "$MAILBOX_USERNAME" > /dev/null 2>&1; then
    # Add group
    groupadd "$MAILBOX_USERNAME"

    # Add user
    useradd --create-home --shell /bin/bash --no-user-group "$MAILBOX_USERNAME"

    # Assign user to group
    usermod -g "$MAILBOX_USERNAME" "$MAILBOX_USERNAME"
fi

# Set passwords
echo "$MAILBOX_USERNAME":"$MAILBOX_PASSWORD" | chpasswd

# Create mailbox
mkdir -p -- \
    "/var/mail/${MAILBOX_USERNAME}" \
    "/var/mail/${MAILBOX_USERNAME}/.mail" \
    "/var/mail/${MAILBOX_USERNAME}/.mail/Archive" \
    "/var/mail/${MAILBOX_USERNAME}/.mail/Drafts" \
    "/var/mail/${MAILBOX_USERNAME}/.mail/Sent" \
    "/var/mail/${MAILBOX_USERNAME}/.mail/Spam" \
    "/var/mail/${MAILBOX_USERNAME}/.mail/Tash"

# Fix permissions
chown -R "${MAILBOX_USERNAME}:${MAILBOX_USERNAME}" --  "/var/mail/${MAILBOX_USERNAME}"
