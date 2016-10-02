Snapshot
========

Mysql snapshot utility


## Features

* Backup single database, or all databases on a server, to Amazon S3
* Compressesion
* Encryption (GPG)
* Supports multiple servers in single config
* Supports multiple storage backends in single config
* Simple database restore
* Lists remote snapshots with wildcards

## Configuration

Snapshot is configured using a `snapshot.yml` file.

The file will be automatically loaded from the current working directory, or from `/etc/snapshot.yml`.

You can also pass `--config` or `-c` to specify the exact config filename you wish to use.

To get you started, simply run:

  cp snapshot.yml.dist snapshot.yml

and edit `snapshot.yml` to fit your environment

### Example snapshot.yml

In the following config you'll find 2 configured database servers (`server-a` and `server-b`), and one storage backend (`store1`):

```yml
workdir: /snapshot

servers:
  
  server-a:
    username: root
    password: super_secret_password
    address: 10.0.0.100
    port: 3306
    
  server-b:
    username: root
    password: mega_secret_password
    address: 10.0.0.101
    port: 3306
    
    
storage:
  store1:
    type: s3
    region: eu-west-1
    access_key: HELLO
    secret_key: SHHHHHHH
    bucket: my_bucket_name
    prefix: "snapshot/"
    gpg_password: s3cr3t
```

## Usage examples:

### Backing up a whole server:

The following command will backup all databases on `server-a` to `store1`.

  bin/snapshot server:backup server-a store1

### Backing up a single database:

The following command will backup database `my_db` on `server-a` to `store1`.

  bin/snapshot database:backup server-a my_db store1

### List remote snapshots

The following command will list all snapshots in `store1`.

  bin/snapshot snapshot:list store1

The following command will list all snapshots in `store1` matching a filter.

  bin/snapshot snapshot:list store1 snapshot-a/*/my_db

## Restoring backups

The following command will restore a backup of snapshot `server-a/20160101/my_db` from `store1` onto `server-b`:

  bin/snapshot snapshot:restore store1 server-b server-a/20160101/my_db

## License

MIT. Please refer to the [license file](LICENSE.md) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
