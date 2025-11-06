<?php

namespace App\Controller;

use App\Model\CountryScenarios;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// Удали: use Twig\Environment as TwigEnvironment;

// CountryPagesController - контроллер для работы со странами через HTML-страницы (MVC)
#[Route('/countries')] // Общий префикс для всех маршрутов в этом контроллере
class CountryPagesController extends AbstractController
{
    public function __construct(
        private readonly CountryScenarios $countryScenarios
    ) {
    }

    // Обработчик для отображения списка всех стран
    // ...
    // ...
    #[Route('', name: 'countries_list', methods: ['GET'])]
    public function list(): Response
    {
        $countries = $this->countryScenarios->getAll();
        error_log("DEBUG: CountryPagesController::list() fetched " . count($countries) . " countries.");
        // Подготавливаем переменные для "шаблона"
        $title = 'Countries List';
        $content = '';
        $flash_message = $flash_message ?? null; // Если используешь flash-сообщения
        $flash_type = $flash_type ?? null;     // Если используешь flash-сообщения

        // Буферизируем вывод из "шаблона"
        ob_start();
        error_log("DEBUG: Including countries_list.php template.");
        // Передаём переменные в "шаблон" через $GLOBALS
        // Это позволяет избежать extract() внутри шаблона, что может быть более предсказуемо
        $template_vars = [
            'countries' => $countries,
            'title' => $title,
            'content' => $content,
            'flash_message' => $flash_message ?? null, // Раскомментируй
            'flash_type' => $flash_type ?? null,     // Раскомментируй
        ];
        // extract($template_vars); // <-- УБРАТЬ extract
        // Вместо этого, передаём переменные через $GLOBALS
        foreach ($template_vars as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        include __DIR__ . '/../../public/views/countries_list.php'; // <--- ИСПОЛЬЗУЕМ ЧИСТЫЙ PHP-ШАБЛОН
        error_log("DEBUG: After including countries_list.php template.");
        $list_content = ob_get_clean(); // Получаем СОДЕРЖИМОЕ СПИСКА (таблицу)

        // Теперь формируем ПОЛНУЮ страницу, включая базовый шаблон
        ob_start();
        // Подготовим переменные для base.php
        $base_template_vars = [
            'title' => $title,
            'content' => $list_content, // Передаём сгенерированное содержимое списка
            'flash_message' => $flash_message,
            'flash_type' => $flash_type
        ];
        foreach ($base_template_vars as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        error_log("DEBUG: Including base.php template.");
        include __DIR__ . '/../../public/views/base.php'; // <-- ВКЛЮЧАЕМ БАЗОВЫЙ ШАБЛОН СНАРУЖИ
        error_log("DEBUG: After including base.php template.");
        $page_content = ob_get_clean(); // Получаем ПОЛНУЮ HTML-страницу

        // Возвращаем Response с HTML-контентом
        return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
}



    // Обработчик для отображения формы добавления
    #[Route('/new', name: 'countries_new_form', methods: ['GET'])]
    public function newForm(): Response
{
    $title = 'Add New Country';
    $action = 'add';
    // Создай объект Country с *пустыми значениями* или используй null, если конструктор позволяет
    // Но, т.к. все поля public и final, единственный способ создать объект - это передать все 7 значений
    $country = new \App\Model\Country(
        shortName: '',
        fullName: '',
        isoAlpha2: '',
        isoAlpha3: '',
        isoNumeric: '',
        population: 0,
        square: 0.0
    );
    $error = null;

    ob_start();
    $template_vars = [
        'country' => $country,
        'action' => $action,
        'title' => $title,
        'error' => $error
    ];
    extract($template_vars);
    include __DIR__ . '/../../public/views/countries_form.php'; // <- ИСПОЛЬЗУЕМ ЧИСТЫЙ PHP-ШАБЛОН
    $page_content = ob_get_clean();

    return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
    }

    // Обработчик для обработки отправки формы добавления
    #[Route('/new', name: 'countries_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Проверка CSRF токена (упрощено)
        $submittedToken = $request->request->get('_token');
        // $expectedToken = ... (должен быть сгенерирован ранее и сохранён в сессии)
        // if (!hash_equals($expectedToken, $submittedToken)) {
        //     throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        // }

        // Получить данные из формы (POST)
        $data = $request->request->all();

        // Создать объект Country из данных формы
        $country = new \App\Model\Country(
            shortName: $data['shortName'],
            fullName: $data['fullName'],
            isoAlpha2: $data['isoAlpha2'],
            isoAlpha3: $data['isoAlpha3'],
            isoNumeric: $data['isoNumeric'],
            population: (int)$data['population'],
            square: (float)$data['square']
        );

        try {
            // Вызвать метод модели для сохранения
            $this->countryScenarios->store($country);
            // Установить flash-сообщение (упрощено)
            $flash_message = 'Country added successfully!';
            $flash_type = 'success';

            // Показать список с сообщением
            $countries = $this->countryScenarios->getAll();
            $title = 'Countries List';
            $content = '';
            // $flash_message и $flash_type уже установлены выше

            ob_start();
            $template_vars = [
                'countries' => $countries,
                'title' => $title,
                'content' => $content,
                'flash_message' => $flash_message,
                'flash_type' => $flash_type
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_list.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);

        } catch (\Exception $e) {
            // Обработка ошибки (например, валидация, дубликат)
            $error = $e->getMessage();
            $title = 'Add New Country';
            $action = 'add';

            ob_start();
            $template_vars = [
                'country' => $country, // Возвращаем введённые данные
                'action' => $action,
                'title' => $title,
                'error' => $error
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_form.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
        }
    }

    // Обработчик для отображения формы редактирования
    #[Route('/{code}/edit', name: 'countries_edit_form', methods: ['GET'])]
    public function editForm(string $code): Response
    {
        try {
            // Получить страну по коду
            $country = $this->countryScenarios->get($code);
            $title = "Edit Country: " . $country->shortName;
            $action = 'edit';
            $originalCountry = $country; // Для шаблона
            $error = null;

            ob_start();
            $template_vars = [
                'country' => $country,
                'action' => $action,
                'title' => $title,
                'originalCountry' => $originalCountry,
                'error' => $error
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_form.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);

        } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
            // Если страна не найдена, вернуть 404
            throw $this->createNotFoundException('Country not found');
        } catch (\App\Model\Exceptions\InvalidCodeException $e) {
            // Если код невалиден, тоже 404 или 400
            throw $this->createNotFoundException('Invalid country code');
        }
    }

    // Обработчик для обработки отправки формы редактирования
    #[Route('/{code}/edit', name: 'countries_update', methods: ['POST'])]
    public function update(string $code, Request $request): Response
    {
        // Проверка CSRF токена (упрощено)
        $submittedToken = $request->request->get('_token');
        // if (!hash_equals($expectedToken, $submittedToken)) {
        //     throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        // }

        // Получить данные из формы (POST)
        $data = $request->request->all();

        // Создать объект Country из данных формы
        $country = new \App\Model\Country(
            shortName: $data['shortName'],
            fullName: $data['fullName'],
            isoAlpha2: $data['isoAlpha2'], // Эти коды не должны меняться, но мы их передаём для валидации в модели
            isoAlpha3: $data['isoAlpha3'],
            isoNumeric: $data['isoNumeric'],
            population: (int)$data['population'],
            square: (float)$data['square']
        );

        try {
            // Вызвать метод модели для обновления
            $this->countryScenarios->edit($code, $country);
            // Установить flash-сообщение
            $flash_message = 'Country updated successfully!';
            $flash_type = 'success';

            // Показать список с сообщением
            $countries = $this->countryScenarios->getAll();
            $title = 'Countries List';
            $content = '';
            // $flash_message и $flash_type уже установлены выше

            ob_start();
            $template_vars = [
                'countries' => $countries,
                'title' => $title,
                'content' => $content,
                'flash_message' => $flash_message,
                'flash_type' => $flash_type
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_list.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);

        } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
            throw $this->createNotFoundException('Country not found');
        } catch (\App\Model\Exceptions\InvalidCodeException $e) {
            throw $this->createNotFoundException('Invalid country code');
        } catch (\Exception $e) {
            // Обработка других ошибок (валидация, дубликат)
            try {
                $originalCountry = $this->countryScenarios->get($code); // Получить оригинальную страну снова
            } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
                 throw $this->createNotFoundException('Country not found');
            }
            $error = $e->getMessage();
            $title = "Edit Country: " . $originalCountry->shortName;
            $action = 'edit';

            ob_start();
            $template_vars = [
                'country' => $country, // Возвращаем введённые данные
                'originalCountry' => $originalCountry,
                'action' => $action,
                'title' => $title,
                'error' => $error
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_form.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
        }
    }

    // Обработчик для удаления страны
    #[Route('/{code}/delete', name: 'countries_delete', methods: ['POST'])] // Лучше POST для безопасности
    public function delete(string $code, Request $request): Response // <--- ДОБАВЬ Request $request
    {
        // Проверка CSRF токена (упрощено)
        $submittedToken = $request->request->get('_token'); // <--- Тепер $request доступен
        // if (!hash_equals($expectedToken, $submittedToken)) {
        //     throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        // }

        // Вызвать метод модели для удаления
        try {
            $this->countryScenarios->delete($code); // Используем инъекцию $this->countryScenarios
            $flash_message = 'Country deleted successfully!';
            $flash_type = 'success';
        } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
            // Игнорировать или логировать, если страна уже удалена
            $flash_message = 'Country not found or already deleted.';
            $flash_type = 'warning';
        } catch (\App\Model\Exceptions\InvalidCodeException $e) {
            // Бросаем 404, если код невалиден
            throw $this->createNotFoundException('Invalid country code');
        }

        // Показать список с сообщением
        $countries = $this->countryScenarios->getAll();
        $title = 'Countries List';
        $content = '';
        // $flash_message и $flash_type уже установлены выше

        ob_start();
        $template_vars = [
            'countries' => $countries,
            'title' => $title,
            'content' => $content,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type
        ];
        extract($template_vars);
        include __DIR__ . '/../../public/views/countries_list.php'; // <--- ИСПРАВИЛ ПУТЬ: ../../public/views (было ../../../)
        $page_content = ob_get_clean();

        return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
    }
}