--
-- Project Showcase Database Setup Queries
--
-- This script assumes that the `user` table already exists
--

CREATE TABLE IF NOT EXISTS showcase_user_profile (
    sup_u_id CHAR(16) NOT NULL,
    sup_about TEXT,
    sup_show_contact_info BOOLEAN,
    sup_website_link VARCHAR(512),
    sup_github_link VARCHAR(128),
    sup_linkedin_link VARCHAR(128),
    sup_resume_link VARCHAR(128),

    PRIMARY KEY (sup_u_id),
    FOREIGN KEY (sup_u_id) REFERENCES user (u_id)
);

CREATE TABLE IF NOT EXISTS showcase_project (
    sp_id CHAR(16) NOT NULL,
    sp_title VARCHAR(256) NOT NULL,
    sp_description TEXT,
    sp_published BOOLEAN,
    sp_date_created DATETIME NOT NULL,
    sp_date_updated DATETIME,

    PRIMARY KEY (sp_id)
);

CREATE TABLE IF NOT EXISTS showcase_project_artifact (
    spa_id CHAR(16) NOT NULL,
    spa_sp_id CHAR(16) NOT NULL,
    spa_name VARCHAR(256) NOT NULL,
    spa_description TEXT,
    spa_file VARCHAR(256),
    spa_mime VARCHAR(64),
    spa_link VARCHAR(512),
    spa_published BOOLEAN,

    PRIMARY KEY (spa_id),
    FOREIGN KEY (spa_sp_id) REFERENCES showcase_project (sp_id)
);

CREATE TABLE IF NOT EXISTS showcase_project_worked_on (
    swo_u_id CHAR(16) NOT NULL,
    swo_sp_id CHAR(16) NOT NULL,

    PRIMARY KEY (swo_u_id, swo_sp_id),
    FOREIGN KEY (swo_u_id) REFERENCES user (u_id),
    FOREIGN KEY (swo_sp_id) REFERENCES showcase_project (sp_id)
);