<?php

namespace App\Controller;

use App\Model\CountryScenarios;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\Country;

#[Route('/api/country')] 
class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryScenarios $countryScenarios
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
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid JSON in request body');
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

        } catch (\App\Model\Exceptions\InvalidCodeException $e) {
            // Если выброшено InvalidCodeException, вернуть 400
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException($e->getMessage());
        } catch (\App\Model\Exceptions\DuplicatedCodeException $e) {
            // Если выброшено DuplicatedCodeException, вернуть 409
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException($e->getMessage());
        } catch (\Exception $e) {
            if ($e instanceof \mysqli_sql_exception) {
                // Пример: если ошибка связана с длиной данных, можно вернуть 400
                if (strpos($e->getMessage(), 'Data too long') !== false) {
                    throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Input data is too long for one or more fields.');
                }
            }
            // Для остальных случаев - бросаем дальше, и Symfony сама вернёт 500
            throw $e;
        }
    }
}