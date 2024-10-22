CREATE DATABASE nss;
USE nss;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL UNIQUE,
    user_number BIGINT,
    user_al_number BIGINT
);

-- Roles table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(255) UNIQUE
);

-- Members table
CREATE TABLE members (
    member_id INT NOT NULL,
    role_id INT NOT NULL,
    added_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id, role_id),
    CONSTRAINT fk_member FOREIGN KEY (member_id) REFERENCES users(user_id),
    CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(role_id),
    CONSTRAINT fk_added_by FOREIGN KEY (added_by) REFERENCES users(user_id)
);

-- User photos table
CREATE TABLE user_photos (
    photo_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_photo_url VARCHAR(255),
    uploaded_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Event category table
CREATE TABLE event_category (
    ec_id INT PRIMARY KEY AUTO_INCREMENT,
    ec_name VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(255) NOT NULL,
    ec_id INT NOT NULL,
    hosted_on DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    location VARCHAR(255),
    CONSTRAINT fk_ec FOREIGN KEY (ec_id) REFERENCES event_category(ec_id)
);

-- Event attendees table
CREATE TABLE event_attendees (
    event_id INT NOT NULL,
    member_id INT NOT NULL,
    PRIMARY KEY (event_id, member_id),
    CONSTRAINT fk_event FOREIGN KEY (event_id) REFERENCES events(event_id),
    CONSTRAINT fk_member_attend FOREIGN KEY (member_id) REFERENCES members(member_id)
);

-- Event photos table
CREATE TABLE event_photos (
    photo_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    photo_url VARCHAR(255),
    uploaded_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_photo FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- Units table
CREATE TABLE units (
    unit_id INT PRIMARY KEY AUTO_INCREMENT,
    unit_number INT NOT NULL,
    start_year DATE,
    end_year DATE
);

-- Unit members table
CREATE TABLE unit_members (
    unit_id INT NOT NULL,
    member_id INT NOT NULL,
    joined_on DATE,
    left_on DATE,
    PRIMARY KEY (unit_id, member_id),
    CONSTRAINT fk_unit FOREIGN KEY (unit_id) REFERENCES units(unit_id),
    CONSTRAINT fk_member_unit FOREIGN KEY (member_id) REFERENCES members(member_id)
);
