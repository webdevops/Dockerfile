# webdevops/vsftp

The `vsftpd` FTP server on [`webdevops/base`](base.md) (Ubuntu 22.04).

## Tags

| Tag | Based on |
| --- | --- |
| `latest` | `webdevops/base:latest` (Ubuntu 22.04) |

## Usage

```yaml
services:
  ftp:
    image: webdevops/vsftp:latest
    ports:
      - "21:21"
      - "12020-12025:12020-12025"
    environment:
      FTP_USER: upload
      FTP_PASSWORD: change-me
    volumes:
      - ./ftp:/home/upload
```

The FTP user's home directory is `/home/<FTP_USER>`; uploads are stored there.

Exposed ports: `20`, `21` and `12020` to `12025`. Started by default: `vsftp`.

Settings: [FTP](../environment-variables.md#ftp) and `SERVICE_VSFTP_OPTS`; hooks in `/opt/docker/bin/service.d/vsftp.d`, see [Customization](../customization.md).
