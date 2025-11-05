<?php

namespace App\Controller;

use App\Model\CountryScenarios; // Подключаем класс CountryScenarios
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\Country; // Подключаем класс Country
use App\Model\Exceptions\InvalidCodeException; // Добавь в начало файла
use App\Model\Exceptions\DuplicatedCodeException; // Добавь в начало файла
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; // Для 400
use Symfony\Component\HttpKernel\Exception\ConflictHttpException; // Для 409

#[Route('/api/country')] // Устанавливаем общий маршрут для всех методов в этом контроллере
class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryScenarios $countryScenarios // Инъекция зависимости CountryScenarios
    ) {
    }

    #[Route('', name: 'api_country_get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $countries = $this->countryScenarios->getAll();
        return $this->json($countries);
    }

    #[Route('/{code}', name: 'api_country_get', methods: ['GET'])]
    public function get(string $code): JsonResponse
    {
        $country = $this->countryScenarios->get($code);
        return $this->json($country);
    }

    #[Route('', name: 'api_country_store', methods: ['POST'])]
public function store(Request $request): Response

{
    try {
        // Получить JSON из тела запроса
        $data = json_decode($request->getContent(), true);

        // Проверка на null, если тело не JSON или пустое
        if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
            throw new BadRequestHttpException('Invalid JSON in request body');
        }

        // Создать объект Country из данных
        $country = new Country(
            shortName: $data['shortName'],
            fullName: $data['fullName'],
            isoAlpha2: $data['isoAlpha2'],
            isoAlpha3: $data['isoAlpha3'],
            isoNumeric: $data['isoNumeric'],
            population: (int)$data['population'],
            square: (float)$data['square']
        );

        // Вызвать метод модели
        $this->countryScenarios->store($country);

        // Вернуть 204 No Content
        return new Response(status: Response::HTTP_NO_CONTENT);

    } catch (InvalidCodeException $e) {
        // Если выброшено InvalidCodeException, вернуть 400
        throw new BadRequestHttpException($e->getMessage());
    } catch (DuplicatedCodeException $e) {
        // Если выброшено DuplicatedCodeException, вернуть 409
        throw new ConflictHttpException($e->getMessage());
    } catch (\Exception $e) {
        // Логируем общее исключение, если нужно
        // error_log($e->getMessage());
        // Любая другая ошибка (например, SQL ошибка из-за длины строки) -> 500
        // Но можно добавить обработку конкретных SQL ошибок, если хочется быть точнее
        if ($e instanceof \mysqli_sql_exception) {
            // Пример: если ошибка связана с длиной данных, можно вернуть 400
            if (strpos($e->getMessage(), 'Data too long') !== false) {
                throw new BadRequestHttpException('Input data is too long for one or more fields.');
            }
        }
        // Для остальных случаев - бросаем дальше, и Symfony сама вернёт 500
        throw $e;
    }
}

#[Route('/{code}', name: 'api_country_edit', methods: ['PATCH'])]
public function edit(string $code, Request $request): JsonResponse
{
    try {
        // Получить JSON из тела запроса
        $data = json_decode($request->getContent(), true);

        // Проверка на null, если тело не JSON или пустое
        if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
            throw new BadRequestHttpException('Invalid JSON in request body');
        }

        // Получим текущую страну, чтобы скопировать коды
        $currentCountry = $this->countryScenarios->get($code); // Этот метод уже проверяет валидность кода и существование

        // Создать объект Country из данных, используя старые коды
        $country = new Country(
            shortName: $data['shortName'] ?? $currentCountry->shortName, // Используем старое значение, если не передано новое
            fullName: $data['fullName'] ?? $currentCountry->fullName,
            isoAlpha2: $currentCountry->isoAlpha2, // Коды НЕ изменяются
            isoAlpha3: $currentCountry->isoAlpha3,
            isoNumeric: $currentCountry->isoNumeric,
            population: (int)($data['population'] ?? $currentCountry->population),
            square: (float)($data['square'] ?? $currentCountry->square)
        );

        // Вызвать метод модели
        $this->countryScenarios->edit($code, $country);

        // Вернуть обновлённый объект страны (200 OK)
        return $this->json($country);

    } catch (InvalidCodeException $e) {
        throw new BadRequestHttpException($e->getMessage());
    } catch (CountryNotFoundException $e) {
        throw new NotFoundHttpException($e->getMessage());
    } catch (DuplicatedCodeException $e) {
        throw new ConflictHttpException($e->getMessage());
    } catch (\Exception $e) {
        // Логируем общее исключение, если нужно
        // error_log($e->getMessage());
        // Любая другая ошибка -> 500
        // Но можно добавить обработку конкретных SQL ошибок, если хочется быть точнее
        if ($e instanceof \mysqli_sql_exception) {
            // Пример: если ошибка связана с длиной данных, можно вернуть 400
            if (strpos($e->getMessage(), 'Data too long') !== false) {
                throw new BadRequestHttpException('Input data is too long for one or more fields.');
            }
        }
        // Для остальных случаев - бросаем дальше, и Symfony сама вернёт 500
        throw $e;
    }
}

#[Route('/{code}', name: 'api_country_delete', methods: ['DELETE'])]
public function delete(string $code): Response
{
    try {
        // Вызвать метод модели
        $this->countryScenarios->delete($code);

        // Вернуть 204 No Content
        return new Response(status: Response::HTTP_NO_CONTENT);

    } catch (InvalidCodeException $e) {
        throw new BadRequestHttpException($e->getMessage());
    } catch (CountryNotFoundException $e) {
        throw new NotFoundHttpException($e->getMessage());
    }
}
}