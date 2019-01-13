-- =============== EXTENSIONS ===============

-- citext (Case Insensitive text)
CREATE EXTENSION
    IF NOT EXISTS "citext";

-- uuid-ossp (UUID generation functions)
CREATE EXTENSION
    IF NOT EXISTS "uuid-ossp";


-- =============== CUSTOM DOMAINS ===============

-- USERNAME_TYPE
-- Support to username type (alfanumeric string, strating with a letter and maximum 20 chars)
CREATE DOMAIN USERSAME_TYPE AS citext
    CHECK (value ~ '^[a-zA-Z][a-zA-z0-9]{4,20}$');

-- EMAIL_TYPE
-- Support to email type (it is based on the citext module)
CREATE DOMAIN EMAIL_TYPE AS citext
    CHECK (value ~ '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$');


-- =============== TABLES ===============

-- Users
-- It will contain all users of the application
CREATE TABLE users (
    uuid UUID,
    username USERSAME_TYPE,
    email EMAIL_TYPE,
    password text,
    email_confirmed BOOLEAN NOT NULL
        DEFAULT FALSE,
    registration_date TIMESTAMP WITH TIME ZONE NOT NULL
        DEFAULT NOW(),
    last_login TIMESTAMP WITH TIME ZONE NOT NULL
        DEFAULT NOW(),
    activated BOOLEAN NOT NULL
        DEFAULT TRUE,
    deleted BOOLEAN NOT NULL
        DEFAULT FALSE,

    PRIMARY KEY (uuid),
    UNIQUE (username),
    UNIQUE (email)
);