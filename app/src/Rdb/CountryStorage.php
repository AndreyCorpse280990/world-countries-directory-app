<?php

namespace App\Rdb; 

use App\Model\Country;
use App\Model\CountryRepository; 
use App\Model\Exceptions\CountryNotFoundException;
use App\Model\Exceptions\DuplicatedCodeException;
use App\Rdb\SqlHelper;


// CountryStorage - реализация интерфейса CountryRepository для работы с реляционной БД
class CountryStorage implements CountryRepository 
{

    public function __construct(
        private readonly SqlHelper $sqlHelper
    ) {
        $this->sqlHelper->pingDb(); 
    }

    // selectAll - получение всех стран
    public function selectAll(): array
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            $queryStr = 'SELECT short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f FROM country_t';
            $stmt = $connection->prepare($queryStr);
            $stmt->execute();
            $result = $stmt->get_result();
            $countries = [];
            while ($row = $result->fetch_assoc()) {
                // Создаём объект Country из строки результата
                $countries[] = new \App\Model\Country(
                    shortName: $row['short_name_f'],
                    fullName: $row['full_name_f'],
                    isoAlpha2: $row['iso_alpha2_f'],
                    isoAlpha3: $row['iso_alpha3_f'],
                    isoNumeric: $row['iso_numeric_f'],
                    population: (int)$row['population_f'],
                    square: (float)$row['square_f']
                );
            }
            return $countries;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }

    // selectByCode - получить страну по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    public function selectByCode(string $code): ?Country
    {
        // Проверим, является ли код числовым (isoNumeric)
        if (is_numeric($code)) {
            return $this->selectByIsoNumeric($code);
        } else if (strlen($code) === 2) {
            // Предполагаем, что это isoAlpha2
            return $this->selectByIsoAlpha2($code);
        } else if (strlen($code) === 3) {
            // Предполагаем, что это isoAlpha3
            return $this->selectByIsoAlpha3($code);
        }
        // Если формат не распознан, возвращаем null
        return null;
    }

    // save - сохранение страны в БД
    public function save(Country $country): void
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            // Проверим уникальность кодов
            if ($this->selectByIsoAlpha2($country->isoAlpha2) !== null) {
                throw new DuplicatedCodeException($country->isoAlpha2);
            }
            if ($this->selectByIsoAlpha3($country->isoAlpha3) !== null) {
                throw new DuplicatedCodeException($country->isoAlpha3);
            }
            if ($this->selectByIsoNumeric($country->isoNumeric) !== null) {
                throw new DuplicatedCodeException($country->isoNumeric);
            }

            // Подготовить запрос INSERT
            $queryStr = 'INSERT INTO country_t (short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f)
                        VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }

            $squareInt = (int)$country->square;

            $stmt->bind_param(
                'ssssiii', 
                $country->shortName,
                $country->fullName,
                $country->isoAlpha2,
                $country->isoAlpha3,
                $country->isoNumeric,
                $country->population,
                $squareInt 
            );

            if (!$stmt->execute()) {
                throw new \Exception("Execute failed: " . $stmt->error);
            }
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }

    // deleteByCode - удаление страны по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    public function deleteByCode(string $code): void
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            // Проверим, существует ли страна с указанным кодом
            $existingCountry = $this->selectByCode($code);
            if ($existingCountry === null) {
                throw new CountryNotFoundException($code);
            }

            // Подготовить запрос DELETE
            $queryStr = 'DELETE FROM country_t WHERE iso_alpha2_f = ? OR iso_alpha3_f = ? OR iso_numeric_f = ?';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }

            $stmt->bind_param('sss', $code, $code, $code);

            if (!$stmt->execute()) {
                throw new \Exception("Execute failed: " . $stmt->error);
            }

            // Проверим, был ли удалён хотя бы один ряд
            if ($stmt->affected_rows === 0) {
                throw new CountryNotFoundException($code);
            }
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }

    // updateByCode - обновление данных страны по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    public function updateByCode(string $code, Country $country): void
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            $existingCountry = $this->selectByCode($code);
            if ($existingCountry === null) {
                throw new CountryNotFoundException($code);
            }

            $oldIsoAlpha2 = $existingCountry->isoAlpha2;
            $oldIsoAlpha3 = $existingCountry->isoAlpha3;
            $oldIsoNumeric = $existingCountry->isoNumeric;

            // Проверим, не совпадают ли новые коды с уже существующими, исключая текущую страну
            $existingByNewAlpha2 = $this->selectByIsoAlpha2($country->isoAlpha2);
            if ($existingByNewAlpha2 !== null && $existingByNewAlpha2->isoAlpha2 !== $oldIsoAlpha2) {
                throw new DuplicatedCodeException($country->isoAlpha2);
            }

            $existingByNewAlpha3 = $this->selectByIsoAlpha3($country->isoAlpha3);
            if ($existingByNewAlpha3 !== null && $existingByNewAlpha3->isoAlpha3 !== $oldIsoAlpha3) {
                throw new DuplicatedCodeException($country->isoAlpha3);
            }

            $existingByNewNumeric = $this->selectByIsoNumeric($country->isoNumeric);
            if ($existingByNewNumeric !== null && $existingByNewNumeric->isoNumeric !== $oldIsoNumeric) {
                throw new DuplicatedCodeException($country->isoNumeric);
            }

            // Подготовить запрос UPDATE
            $queryStr = 'UPDATE country_t
                        SET short_name_f = ?, full_name_f = ?, population_f = ?, square_f = ?
                        WHERE iso_alpha2_f = ? AND iso_alpha3_f = ? AND iso_numeric_f = ?';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }

            // Создадим переменные для всех значений, которые передаются в bind_param
            $shortName = $country->shortName;
            $fullName = $country->fullName;
            $population = $country->population;
            $squareInt = (int)$country->square; // Приводим к int и сохраняем в переменную
            $whereIsoAlpha2 = $oldIsoAlpha2;
            $whereIsoAlpha3 = $oldIsoAlpha3;
            $whereIsoNumeric = $oldIsoNumeric;

            // Теперь передаём переменные, а не выражения
            $stmt->bind_param(
                'ssiiiss', // s - строка, i - целое число
                $shortName,       
                $fullName,        
                $population,      
                $squareInt,       
                $whereIsoAlpha2,  
                $whereIsoAlpha3,  
                $whereIsoNumeric  
            );

            if (!$stmt->execute()) {
                throw new \Exception("Execute failed: " . $stmt->error);
            }

            // Проверим, был ли обновлён хотя бы один ряд
            if ($stmt->affected_rows === 0) {
            }
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }


    // selectByIsoAlpha2 - получить страну по isoAlpha2
    public function selectByIsoAlpha2(string $code): ?Country
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            $queryStr = 'SELECT short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f FROM country_t WHERE iso_alpha2_f = ? LIMIT 1;';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                return new Country(
                    shortName: $row['short_name_f'],
                    fullName: $row['full_name_f'],
                    isoAlpha2: $row['iso_alpha2_f'],
                    isoAlpha3: $row['iso_alpha3_f'],
                    isoNumeric: $row['iso_numeric_f'],
                    population: (int)$row['population_f'],
                    square: (float)$row['square_f']
                );
            }

            return null;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }

    // selectByIsoAlpha3 - получить страну по isoAlpha3
    public function selectByIsoAlpha3(string $code): ?Country
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            $queryStr = 'SELECT short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f FROM country_t WHERE iso_alpha3_f = ? LIMIT 1;';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                return new Country(
                    shortName: $row['short_name_f'],
                    fullName: $row['full_name_f'],
                    isoAlpha2: $row['iso_alpha2_f'],
                    isoAlpha3: $row['iso_alpha3_f'],
                    isoNumeric: $row['iso_numeric_f'],
                    population: (int)$row['population_f'],
                    square: (float)$row['square_f']
                );
            }

            return null;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }


    // selectByIsoNumeric - получить страну по isoNumeric
    public function selectByIsoNumeric(string $code): ?Country
    {
        $connection = $this->sqlHelper->openDbConnection();
        try {
            $queryStr = 'SELECT short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f FROM country_t WHERE iso_numeric_f = ? LIMIT 1;';
            $stmt = $connection->prepare($queryStr);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $connection->error);
            }
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                return new Country(
                    shortName: $row['short_name_f'],
                    fullName: $row['full_name_f'],
                    isoAlpha2: $row['iso_alpha2_f'],
                    isoAlpha3: $row['iso_alpha3_f'],
                    isoNumeric: $row['iso_numeric_f'],
                    population: (int)$row['population_f'],
                    square: (float)$row['square_f']
                );
            }

            return null;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
    }
}