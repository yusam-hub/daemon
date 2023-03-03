#### yusam-hub/daemon

    "php": "^7.4|^8.0|^8.1|^8.2"

#### tests

    php ./bin/daemon.php
    sh phpinit

#### setup

    "repositories": {
        ...
        "yusam-hub/daemon": {
            "type": "git",
            "url": "https://github.com/yusam-hub/daemon.git"
        }
        ...
    },
    "require": {
        ...
        "yusam-hub/daemon": "dev-master"
        ...
    }