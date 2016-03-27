# Postfix container layout

## Environment variables

Variable             | Description
-------------------- | ------------------------------------------------------------------------------
`POSTFIX_MYNETWORKS` | Postfix mynetwork setting
`POSTFIX_RELAYHOST`  | Postfix relayhost setting

## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/postfix:latest            | [![](https://badge.imagelayers.io/webdevops/postfix:latest.svg)](https://imagelayers.io/?images=webdevops/postfix:latest 'Get your own badge on imagelayers.io')

## Example usage

Running a sphinx-autobuild server for Live preview.

```bash
docker run -t -i --rm -p 8080:8000 -v <yourDocsDirectory>:/opt/docs webdevops/sphinx sphinx-autobuild -H 0.0.0.0 /opt/docs html
```
