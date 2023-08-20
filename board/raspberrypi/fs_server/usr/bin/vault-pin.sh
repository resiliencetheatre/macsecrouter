#!/bin/sh
echo $1 | sha256sum | cut -d" " -f1 > /tmp/vaultkey
/sbin/cryptsetup luksOpen --key-file /tmp/vaultkey /mnt/vault_0 volume1 && echo -n "Valid pin!"
exit 0;

