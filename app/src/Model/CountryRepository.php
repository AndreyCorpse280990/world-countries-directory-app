<?php

namespace App\Model;

// CountryRepository - интерфейс хранилища стран
interface CountryRepository
{
    // selectAll - получение всех стран
    public function selectAll(): array;

    // selectByCode - получить страну по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    public function selectByCode(string $code): ?Country;

    // save - сохранение страны в БД
    // вход: объект страны
    // выход: -
    // исключения: DuplicatedCodeException
    public function save(Country $country): void;

    // deleteByCode - удаление страны по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    public function deleteByCode(string $code): void;

    // updateByCode - обновление данных страны по коду (isoAlpha2, isoAlpha3 или isoNumeric)
    // вход: код редактируемой страны (не обновленный), объект обновленной страны
    // выход: -
    // исключения: CountryNotFoundException, DuplicatedCodeException
    public function updateByCode(string $code, Country $country): void;

    // selectByIsoAlpha2 - получить страну по isoAlpha2
    public function selectByIsoAlpha2(string $code): ?Country;

    // selectByIsoAlpha3 - получить страну по isoAlpha3
    public function selectByIsoAlpha3(string $code): ?Country;

    // selectByIsoNumeric - получить страну по isoNumeric
    public function selectByIsoNumeric(string $code): ?Country;
}