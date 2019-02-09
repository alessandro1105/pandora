
DROP DOMAIN IF EXISTS FILENAME_TYPE CASCADE;
DROP DOMAIN IF EXISTS FILESIZE CASCADE;
DROP TABLE IF EXISTS file CASCADE;
DROP TABLE IF EXISTS version CASCADE;
DROP TABLE IF EXISTS has_parent CASCADE;

-- =============== EXTENSIONS ===============

-- uuid-ossp (UUID generation functions)
CREATE EXTENSION
    IF NOT EXISTS "uuid-ossp";

    -- =============== CUSTOM DOMAINS ===============

    -- FILENAME_TYPE
    -- Cannot check whether a filename contains / (the root has it) so only the nonEmpty check is kept.
    -- The php modules has the / check for a non root file
CREATE DOMAIN FILENAME_TYPE AS VARCHAR(255)
        CHECK (value ~ '^.+$');


CREATE DOMAIN FILESIZE AS INT
        CHECK (value >= 0);

-- =============== TABLES ===============




--tables in the storage db

--default generator in uuid useful when creating a new directory
CREATE TABLE file (

    uuid UUID NOT NULL,
    file_name FILENAME_TYPE NOT NULL,
    user_uuid UUID NOT NULL,
    is_dir BOOLEAN NOT NULL DEFAULT FALSE, --it also accepts 1 or '1' and 0 or '0'
    creation_time TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

    PRIMARY KEY (uuid)

);

CREATE TABLE version (

    uuid UUID NOT NULL,
    version_number SERIAL,
    creation_time  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    file_size FILESIZE NOT NULL,
    uuid_file UUID NOT NULL,

    PRIMARY KEY (uuid),
    FOREIGN KEY (uuid_file) REFERENCES file(uuid)

);

--default null in uuid_parent useful when creating a root for a user
CREATE TABLE has_parent (

    uuid_child UUID NOT NULL,
    uuid_parent UUID DEFAULT NULL,

    PRIMARY KEY (uuid_child),
    FOREIGN KEY (uuid_child) REFERENCES file(uuid),
    FOREIGN KEY (uuid_parent) REFERENCES file(uuid)

);
