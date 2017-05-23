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
    "~${MAILBOX_USERNAME}" \
    "~${MAILBOX_USERNAME}/.mail" \
    "~${MAILBOX_USERNAME}/.mail/Archive" \
    "~${MAILBOX_USERNAME}/.mail/Drafts" \
    "~${MAILBOX_USERNAME}/.mail/Sent" \
    "~${MAILBOX_USERNAME}/.mail/Spam" \
    "~${MAILBOX_USERNAME}/.mail/Tash"

# Fix permissions
chown -R "${MAILBOX_USERNAME}:${MAILBOX_USERNAME}" --  "~${MAILBOX_USERNAME}"
