<?php

namespace App\Model;

use App\Model\Exceptions\CountryNotFoundException;
use App\Model\Exceptions\InvalidCodeException;
use App\Model\Exceptions\DuplicatedCodeException;

// CountryScenarios - класс с методами работы с объектами стран
class CountryScenarios
{
    public function __construct(
        private readonly CountryRepository $repository 
    ) {
    }

    // getAll - получение всех стран
    // вход: -
    // выход: список объектов Country
    // исключения: -
    public function getAll(): array
    {
        return $this->repository->selectAll();
    }

    // get - получение страны по коду
    public function get(string $code): Country
    {
        // Валидация кода
        if (!$this->isValidCode($code)) {
            throw new InvalidCodeException($code, 'Code format is invalid. Expected 2-letter, 3-letter, or numeric code.');
        }

        // Поиск в хранилище
        $country = $this->repository->selectByCode($code);

        if ($country === null) {
            // Если не найдено - выбросить ошибку
            throw new CountryNotFoundException($code);
        }

        //  вернуть полученный объект
        return $country;
    }

    // isValidCode - проверка корректности кода страны
    private function isValidCode(string $code): bool {
        // Проверяет, состоит ли строка из 2 заглавных букв, 3 заглавных букв или 3 цифр
        return preg_match('/^[A-Z]{2}$/', $code) || preg_match('/^[A-Z]{3}$/', $code) || preg_match('/^[0-9]{3}$/', $code);
    }

    // store - сохранение новой страны
    public function store(Country $country): void
    {
        // Валидация кодов
        if (!$this->isValidCode($country->isoAlpha2)) {
            throw new InvalidCodeException($country->isoAlpha2, 'isoAlpha2 format is invalid.');
        }
        if (!$this->isValidCode($country->isoAlpha3)) {
            throw new InvalidCodeException($country->isoAlpha3, 'isoAlpha3 format is invalid.');
        }
        if (!$this->isValidCode($country->isoNumeric)) {
            throw new InvalidCodeException($country->isoNumeric, 'isoNumeric format is invalid.');
        }

        // Проверка наименований
        if (empty($country->shortName)) {
            throw new InvalidCodeException('shortName', 'shortName cannot be empty.');
        }
        if (empty($country->fullName)) {
            throw new InvalidCodeException('fullName', 'fullName cannot be empty.');
        }

        // Проверка населения и площади
        if ($country->population < 0) {
            throw new InvalidCodeException('population', 'population cannot be negative.');
        }
        if ($country->square < 0) {
            throw new InvalidCodeException('square', 'square cannot be negative.');
        }

        // Сохранение в хранилище 
        $this->repository->save($country);
    }

         // edit - редактирование страны по коду
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

        // delete - удаление страны по коду
        public function delete(string $code): void
        {
            // Валидация кода
            if (!$this->isValidCode($code)) {
                throw new InvalidCodeException($code, 'Code format is invalid. Expected 2-letter, 3-letter, or numeric code.');
            }

            // Получим страну по коду, чтобы проверить её существование
            // Если не найдена - метод selectByCode выбросит CountryNotFoundException
            $country = $this->repository->selectByCode($code);
            if ($country === null) {
                throw new CountryNotFoundException($code);
            }

            // Удаление через хранилище
            $this->repository->deleteByCode($code);
        }
}