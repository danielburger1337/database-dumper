[![Publish](https://github.com/danielburger1337/database-dumper/actions/workflows/publish.yml/badge.svg)](https://github.com/danielburger1337/database-dumper/actions/workflows/publish.yml)
[![PHPStan](https://github.com/danielburger1337/database-dumper/actions/workflows/phpstan.yml/badge.svg)](https://github.com/danielburger1337/database-dumper/actions/workflows/phpstan.yml)
[![PHPCS-Fixer](https://github.com/danielburger1337/database-dumper/actions/workflows/phpcsfixer.yml/badge.svg)](https://github.com/danielburger1337/database-dumper/actions/workflows/phpcsfixer.yml)

# Database Dumper

This tool automatically dumps your database(s) and either saves them to a specified directory or uploads them to a remote server.
Currently SFTP and FTP are support. See [FilesystemFactoryInterface](src/Filesystem/FilesystemFactoryInterface.php) to implement new filesystem support (it uses [league/flystem](https://github.com/thephpleague/flysystem) internally).

It also has a very simple and optional feature to automatically remove old database dumps from the given filesystem.
This currently only supports keeping the latest `N` files.
If custom "algorithms" are desired, see [CleanupAlgorithmInterface](src/Cleanup/CleanupAlgorithmInterface.php) to implement your own.

This tool currently supports MySQL 8 / MariaDB 10 databases.
To implement more database platforms, see [DumperInterface](src/Dumper/DumperInterface.php) and [MySQLDumper](src/Dumper/MySQLDumper.php) for a reference implementation.

## How To use

```yml
version: "3.8"

services:
    db_dumper:
        image: danielburger1337/database-dumper

        # The container automatically shutsdown after a period of time to prevent memory leaks
        # Therefor it must be restarted automatically.
        restart: unless-stopped

        logging:
            driver: "json-file"
            options:
                max-size: "200k"
                max-file: "3"

        environment:
            # By default the database dumps will be stored in the "/data" directory
            - DB_DUMPER_FILESYSTEM_DSN=local://local/data

            # See src/FilesystemFactory/FtpFilesystemFactory.php for all options
            # - DB_DUMPER_FILESYSTEM_DSN=ftp://user:pass@ftp.example.com
            # - DB_DUMPER_FILESYSTEM_DSN=ftps://user:pass@ftp.example.com/directory-path

            # See src/FilesystemFactory/SftpFilesystemFactory.php for all options
            # - DB_DUMPER_FILESYSTEM_DSN=sftp://user:pass@sftp.example.com/

            # Disable compressing the database dump (enabled by default)
            # - DB_DUMPER_ENABLE_GZIP=false

            # Database connection DSN (this will dump all databases)
            - DB_DUMPER_COMMAND=mysql://root@127.0.0.1
            # This will only dump the "my-database-name" database
            # - DB_DUMPER_COMMAND=mysql://root@127.0.0.1:3306/my-database-name
            # This will dump the "my-database-name" and "my-other-database-name" databases
            # - DB_DUMPER_COMMAND=mysql://root@127.0.0.1:3306/my-database-name%20my-other-database-name

            # Cron-Expression for when the database should be dumped
            # - DB_DUMPER_SCHEDULE=0 0 * * *

            # Cron-Expression for when old database dumps should be removed
            # Set this to an empty string to disable deleting old dumps
            # - DB_DUMPER_CLEANUP_SCHEDULE=30 0 * * *

            # How many old database dumps should be kept (default 5)
            # This option only has an effect if "DB_DUMPER_CLEANUP_SCHEDULE" is defined
            # and "DB_DUMPER_CLEANUP_ALGORITHM" is "keep_latest"
            # - DB_DUMPER_CLEANUP_KEEP_LATEST_COUNT=5

            # Optional: Encrypt the database dump with the following password
            # - DB_DUMPER_ENCRYPTION_PASSWORD=123456
            # openssl enc -d -pbkdf2 -aes-256-cbc -in your_dump.sql.enc -out your_dump.sql -k 123456

        # A volume mount is only required if the local filesystem adapter is used.
        # Make sure to mount the same path as used in your "DB_DUMPER_FILESYSTEM_DSN"
        # environment variable.
        volumes:
            - "./local-data-mount:/data"
```
