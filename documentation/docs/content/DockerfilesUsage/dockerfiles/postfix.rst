=================
webdevops/postfix
=================

These image extends ``webdevops/base`` with a postfix daemon which is running on port 25

Environment variables
---------------------

====================== ============================= =============
Environment variable   Description                   Default
====================== ============================= =============
``POSTFIX_MYNETWORKS`` Postfix mynetworks address    *empty*
``POSTFIX_RELAYHOST``  Postfix upstream relay server *empty*
====================== ============================= =============

