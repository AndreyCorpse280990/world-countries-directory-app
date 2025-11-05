<?php

namespace App\Rdb; // Используем пространство имён App\Rdb

use App\Model\Country;
use App\Model\CountryRepository; // Подключаем интерфейс
use App\Model\Exceptions\CountryNotFoundException;
use App\Model\Exceptions\DuplicatedCodeException;
use App\Rdb\SqlHelper;


// CountryStorage - реализация интерфейса CountryRepository для работы с реляционной БД
class CountryStorage implements CountryRepository // Класс имплементирует интерфейс CountryRepository
{
    // Инъекция SqlHelper (его мы создадим в пункте 9)
public function __construct(
    private readonly SqlHelper $sqlHelper
) {
    $this->sqlHelper->pingDb(); 
}

   // selectAll - получение всех стран
// вход: -
// выход: список объектов Country
// исключения: -
public function selectAll(): array
{
    $connection = $this->sqlHelper->openDbConnection();
    try {
        $queryStr = 'SELECT short_name_f, full_name_f, iso_alpha2_f, iso_alpha3_f, iso_numeric_f, population_f, square_f FROM country_t ORDER BY id;';
        $result = $connection->query($queryStr);

        if (!$result) {
            throw new \Exception("Database query failed: " . $connection->error);
        }

        $countries = [];
        while ($row = $result->fetch_assoc()) {
            $country = new Country(
                shortName: $row['short_name_f'],
                fullName: $row['full_name_f'],
                isoAlpha2: $row['iso_alpha2_f'],
                isoAlpha3: $row['iso_alpha3_f'],
                isoNumeric: $row['iso_numeric_f'],
                population: (int)$row['population_f'],
                square: (float)$row['square_f']
            );
            $countries[] = $country;
        }

        return $countries;
    } finally {
        $connection->close();
    }
}

    // selectByCode - получить страну по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    // вход: код страны
    // выход: объект извлеченной страны или null
    // исключения: -
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
    // вход: объект страны
    // выход: -
    // исключения: DuplicatedCodeException
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

            // Создаём переменную для square, чтобы передать её в bind_param
            $squareInt = (int)$country->square;

            $stmt->bind_param(
                'ssssiii', // s - строка, i - целое число
                $country->shortName,
                $country->fullName,
                $country->isoAlpha2,
                $country->isoAlpha3,
                $country->isoNumeric,
                $country->population,
                $squareInt // <-- Передаём переменную, а не выражение
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
    // вход: код удаляемой страны
    // выход: -
    // исключения: CountryNotFoundException
    public function deleteByCode(string $code): void
    {
        throw new \Exception('not implemented');
    }

    // updateByCode - обновление данных страны по коду (isoAlpha2, isoAlpha3 или isoNumeric)
// вход: код редактируемой страны (не обновленный), объект обновленной страны
// выход: -
// исключения: CountryNotFoundException, DuplicatedCodeException
public function updateByCode(string $code, Country $country): void
{
    $connection = $this->sqlHelper->openDbConnection();
    try {
        // Проверим, существует ли страна с указанным кодом
        $existingCountry = $this->selectByCode($code);
        if ($existingCountry === null) {
            throw new CountryNotFoundException($code);
        }

        // Проверим уникальность НОВЫХ кодов, но не для текущей редактируемой страны
        // Получим старые коды текущей страны
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
        // Обновляем ВСЕ поля, кроме кодов (предполагается, что коды в WHERE clause не меняются)
        $queryStr = 'UPDATE country_t
                     SET short_name_f = ?, full_name_f = ?, population_f = ?, square_f = ?
                     WHERE iso_alpha2_f = ? OR iso_alpha3_f = ? OR iso_numeric_f = ?';
        $stmt = $connection->prepare($queryStr);
        if (!$stmt) {
            throw new \Exception("Prepare failed: " . $connection->error);
        }

        // Привязываем параметры: новые значения, затем старые коды для WHERE
        $stmt->bind_param(
            'ssiiiss', // s - строка, i - целое число
            $country->shortName,
            $country->fullName,
            $country->population,
            (int)$country->square, // Убедимся, что square - целое число при обновлении
            $oldIsoAlpha2, // Старый isoAlpha2
            $oldIsoAlpha3, // Старый isoAlpha3
            $oldIsoNumeric // Старый isoNumeric
        );

        if (!$stmt->execute()) {
            throw new \Exception("Execute failed: " . $stmt->error);
        }

        // Проверим, был ли обновлён хотя бы один ряд
        if ($stmt->affected_rows === 0) {
            // Это маловероятно, если WHERE clause корректна и страна существовала
            // Но на всякий случай бросим исключение
            throw new CountryNotFoundException($code);
        }
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $connection->close();
    }
}

    // selectByIsoAlpha2 - получить страну по isoAlpha2
// вход: код isoAlpha2
// выход: объект извлеченной страны или null
// исключения: -
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
// вход: код isoAlpha3
// выход: объект извлеченной страны или null
// исключения: -
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


// edit - редактирование страны по коду
// вход: код редактируемой страны (не обновленный), объект обновленной страны
// выход: -
// исключения: InvalidCodeException, CountryNotFoundException, DuplicatedCodeException
public function edit(string $code, Country $country): void
{
    // Валидация кода
    if (!$this->isValidCode($code)) {
        throw new InvalidCodeException($code, 'Code format is invalid. Expected 2-letter, 3-letter, or numeric code.');
    }

    // Получим текущую страну по коду
    $currentCountry = $this->repository->selectByCode($code);
    if ($currentCountry === null) {
        throw new CountryNotFoundException($code);
    }

    // Проверим, не меняются ли коды
    if ($country->isoAlpha2 !== $currentCountry->isoAlpha2 ||
        $country->isoAlpha3 !== $currentCountry->isoAlpha3 ||
        $country->isoNumeric !== $currentCountry->isoNumeric) {
        throw new InvalidCodeException($code, 'Country codes cannot be changed during update.');
    }

    // Проверка валидности новых данных (наименования, население, площадь)
    if (empty($country->shortName)) {
        throw new InvalidCodeException('shortName', 'shortName cannot be empty.');
    }
    if (empty($country->fullName)) {
        throw new InvalidCodeException('fullName', 'fullName cannot be empty.');
    }
    if ($country->population < 0) {
        throw new InvalidCodeException('population', 'population cannot be negative.');
    }
    if ($country->square < 0) {
        throw new InvalidCodeException('square', 'square cannot be negative.');
    }

    // Вызов метода хранилища для обновления
    $this->repository->updateByCode($code, $country);
}

    // selectByIsoNumeric - получить страну по isoNumeric
// вход: код isoNumeric
// выход: объект извлеченной страны или null
// исключения: -
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