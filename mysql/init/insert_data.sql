USE world_countries_db;

-- Вставим тестовые данные
INSERT INTO country_t (short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f) VALUES
('Russia', 'Russian Federation', 'RU', 'RUS', '643', 146150789, 17125191),
('Germany', 'Federal Republic of Germany', 'DE', 'DEU', '276', 83240525, 357022),
('France', 'French Republic', 'FR', 'FRA', '250', 67848156, 643801),
('Japan', 'State of Japan', 'JP', 'JPN', '392', 125681593, 377975),
('Canada', 'Canada', 'CA', 'CAN', '124', 39566248, 9984670),
('Brazil', 'Federative Republic of Brazil', 'BR', 'BRA', '76', 215313491, 8515767),
('India', 'Republic of India', 'IN', 'IND', '356', 1380004385, 3287263),
('China', 'People\'s Republic of China', 'CN', 'CHN', '156', 1439323776, 9596961),
('United States', 'United States of America', 'US', 'USA', '840', 331002651, 9833517),
('Australia', 'Commonwealth of Australia', 'AU', 'AUS', '36', 25690065, 7692024);