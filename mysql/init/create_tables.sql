
CREATE DATABASE IF NOT EXISTS world_countries_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE world_countries_db;


DROP TABLE IF EXISTS country_t;

CREATE TABLE country_t (
    id INT AUTO_INCREMENT PRIMARY KEY,
    short_name_f VARCHAR(255) NOT NULL,
    full_name_f VARCHAR(255) NOT NULL,
    iso_alpha2_f VARCHAR(2) NOT NULL UNIQUE, 
    iso_alpha3_f VARCHAR(3) NOT NULL UNIQUE, 
    iso_numeric_f VARCHAR(3) NOT NULL UNIQUE, 
    population_f BIGINT NOT NULL,
    square_f BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;пшц