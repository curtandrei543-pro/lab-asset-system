-- =====================================================================
-- Computer Laboratory Asset Management System
-- Persona: Mark Anthony Cruz, Computer Laboratory Custodian
-- Main Entity: Assets
-- Schema below matches the provided ERD exactly (table/column names,
-- types, PKs, and FKs unchanged).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lab_asset_system;
USE lab_asset_system;

CREATE TABLE Users
(
  Users_id    int(11)     NOT NULL AUTO_INCREMENT,
  Full_Name   VARCHAR(50) NOT NULL,
  Email       VARCHAR(20) NOT NULL,
  Password    VARCHAR(20) NOT NULL,
  Role        VARCHAR (20)  NOT NULL,
  Category_id INT(11)     NOT NULL,
  PRIMARY KEY (Users_id)
);

CREATE TABLE Activity_logs
(
  Log_id        INT(11)     NOT NULL AUTO_INCREMENT,
  activity      VARCHAR(45) NOT NULL,
  Activity_date DATE        NOT NULL,
  Users_id      int(11)     NOT NULL,
  PRIMARY KEY (Log_id)
);

CREATE TABLE Asset_Categories
(
  Category_id   INT(11)      NOT NULL AUTO_INCREMENT,
  Category_Name VARCHAR(45)  NOT NULL,
  Description   VARCHAR( 50) NOT NULL,
  PRIMARY KEY (Category_id)
);

CREATE TABLE Laboratories
(
  Laboratory_id    INT(11)     NOT NULL AUTO_INCREMENT,
  Laboratrory_Name VARCHAR(20) NOT NULL,
  Location         VARCHAR(20) NOT NULL,
  Capacity         INT(11)     NULL    ,
  PRIMARY KEY (Laboratory_id)
);

CREATE TABLE Assets
(
  Asset_id      INT(11)      NOT NULL AUTO_INCREMENT,
  Asset_name    VARCHAR(45)  NOT NULL,
  Brand         VARCHAR(50) NOT NULL,
  Serial_Number INT(11)      NOT NULL,
  Purchase_Date DATE         NOT NULL,
  Status        VARCHAR(20)  NOT NULL,
  Category_id   INT(11)      NOT NULL,
  Laboratory_id INT(11)      NOT NULL,
  PRIMARY KEY (Asset_id)
);

CREATE TABLE Asset_Assignment
(
  assignment_id INT(11)     NOT NULL AUTO_INCREMENT,
  assigned_id   INT(11)     NOT NULL,
  Remarks       VARCHAR(45) NULL    ,
  Laboratory_id INT(11)     NOT NULL,
  Asset_id      INT(11)     NOT NULL,
  PRIMARY KEY (assignment_id)
);

CREATE TABLE Maintenance_Records
(
  Maintenance_id   INT(11)     NOT NULL AUTO_INCREMENT,
  Maintenace_date  DATE        NOT NULL,
  Maintenance_type VARCHAR(45) NOT NULL,
  Description      VARCHAR(45) NOT NULL,
  Status           VARCHAR(20) NOT NULL,
  Users_id         int(11)     NOT NULL,
  Asset_id         INT(11)     NOT NULL,
  PRIMARY KEY (Maintenance_id)
);

ALTER TABLE Maintenance_Records
  ADD CONSTRAINT FK_Users_TO_Maintenance_Records
    FOREIGN KEY (Users_id)
    REFERENCES Users (Users_id);

ALTER TABLE Activity_logs
  ADD CONSTRAINT FK_Users_TO_Activity_logs
    FOREIGN KEY (Users_id)
    REFERENCES Users (Users_id);

ALTER TABLE Assets
  ADD CONSTRAINT FK_Asset_Categories_TO_Assets
    FOREIGN KEY (Category_id)
    REFERENCES Asset_Categories (Category_id);

ALTER TABLE Assets
  ADD CONSTRAINT FK_Laboratories_TO_Assets
    FOREIGN KEY (Laboratory_id)
    REFERENCES Laboratories (Laboratory_id);

ALTER TABLE Asset_Assignment
  ADD CONSTRAINT FK_Laboratories_TO_Asset_Assignment
    FOREIGN KEY (Laboratory_id)
    REFERENCES Laboratories (Laboratory_id);

ALTER TABLE Maintenance_Records
  ADD CONSTRAINT FK_Assets_TO_Maintenance_Records
    FOREIGN KEY (Asset_id)
    REFERENCES Assets (Asset_id);

ALTER TABLE Asset_Assignment
  ADD CONSTRAINT FK_Assets_TO_Asset_Assignment
    FOREIGN KEY (Asset_id)
    REFERENCES Assets (Asset_id);

-- ---------------------------------------------------------------------
-- Seed data (needed for the Asset CRUD app's dropdowns / demo rows)
-- ---------------------------------------------------------------------
INSERT INTO Asset_Categories (Category_Name, Description) VALUES
    ('Desktop',         'Desktop computer units'),
    ('Monitor',         'Display monitors'),
    ('Peripheral',      'Keyboards, mice, and other accessories'),
    ('Printer',         'Printers and scanners'),
    ('Projector',       'Classroom/lab projectors'),
    ('Network Device',  'Switches, routers, access points');

INSERT INTO Laboratories (Laboratrory_Name, Location, Capacity) VALUES
    ('Lab 1', '2nd Floor',  40),
    ('Lab 2', '3rd Floor',  40),
    ('Lab 3', 'Annex Bldg', 35);

INSERT INTO Users (Full_Name, Email, Password, Role, Category_id) VALUES
    ('Mark Anthony Cruz', 'mcruz@school.edu', 'hashed_pw', 'Custodian', 1);

INSERT INTO Assets (Asset_name, Brand, Serial_Number, Purchase_Date, Status, Category_id, Laboratory_id) VALUES
    ('OptiPlex 3080',        'Dell',      100001, '2023-06-15', 'Working',      1, 1),
    ('19-inch Monitor',      'ViewSonic', 100002, '2023-06-15', 'Damaged',      2, 1),
    ('EB-X05 Projector',     'Epson',     100003, '2022-01-10', 'Under Repair', 5, 2),
    ('24-Port Switch',       'TP-Link',   100004, '2021-09-01', 'Working',      6, 3);
