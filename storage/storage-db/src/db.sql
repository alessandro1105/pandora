--tables in the storage db
CREATE TABLE file (

    uuid varchar(36) NOT NULL,
    file_name varchar(50) NOT NULL,
    user_uuid varchar(36) NOT NULL,
    is_dir tinyint(1) NOT NULL,
    creation_time datetime,
    PRIMARY KEY (uuid)

);

CREATE TABLE version (

    uuid varchar(36) NOT NULL,
    version_number INT NOT NULL AUTO_INCREMENT,
    creation_time datetime,
    file_size int,
    uuid_file varchar(36) NOT NULL,
    PRIMARY KEY (uuid),
    FOREIGN KEY (uuid_file) REFERENCES file(uuid)

);

CREATE TABLE has_parent (

    uuid_child varchar(36) NOT NULL,
    uuid_parent VARCHAR(36),
    PRIMARY KEY (uuid_child),
    FOREIGN KEY (uuid_child) REFERENCES file(uuid),
    FOREIGN KEY (uuid_parent) REFERENCES file(uuid)

);
--possible additional constraint needed: uuid_parent is null if and only if uuid_child correspond to a root directory
