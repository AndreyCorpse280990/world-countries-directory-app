<?php

namespace App\Controller;

use App\Model\CountryScenarios;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// Удали: use Twig\Environment as TwigEnvironment;

// CountryPagesController - контроллер для работы со странами через HTML-страницы (MVC)
#[Route('/countries')]
class CountryPagesController extends AbstractController
{
    public function __construct(
        private readonly CountryScenarios $countryScenarios
    ) {
    }

    // Обработчик для отображения списка всех стран
// ...
    #[Route('', name: 'countries_list', methods: ['GET'])]
    public function list(): Response
    {
        $countries = $this->countryScenarios->getAll();
        // Подготавливаем переменные для "шаблона"
        $title = 'Countries List';
        $content = '';
        $flash_message = $flash_message ?? null; 
        $flash_type = $flash_type ?? null;     

        ob_start();
        $template_vars = [
            'countries' => $countries,
            'title' => $title,
            'content' => $content,
            'flash_message' => $flash_message ?? null, 
            'flash_type' => $flash_type ?? null,     
        ];
        extract($template_vars); 
        include __DIR__ . '/../../public/views/countries_list.php'; 
        $page_content = ob_get_clean(); 

        // Возвращаем Response с HTML-контентом
        return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
    }


    // Обработчик для отображения формы добавления
    #[Route('/new', name: 'countries_new_form', methods: ['GET'])]
    public function newForm(): Response
{
    $title = 'Add New Country';
    $action = 'add';
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
    include __DIR__ . '/../../public/views/countries_form.php'; 
    $page_content = ob_get_clean();

    return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
    }

    // Обработчик для обработки отправки формы добавления
    #[Route('/new', name: 'countries_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Проверка CSRF токена (упрощено)
        $submittedToken = $request->request->get('_token');

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
            $flash_message = 'Country added successfully!';
            $flash_type = 'success';

            // Показать список с сообщением
            $countries = $this->countryScenarios->getAll();
            $title = 'Countries List';
            $content = '';

            ob_start();
            $template_vars = [
                'countries' => $countries,
                'title' => $title,
                'content' => $content,
                'flash_message' => $flash_message,
                'flash_type' => $flash_type
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_list.php'; 
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $title = 'Add New Country';
            $action = 'add';

            ob_start();
            $template_vars = [
                'country' => $country, 
                'action' => $action,
                'title' => $title,
                'error' => $error
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_form.php';
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
        }
    }

    // Обработчик для отображения формы редактирования
    #[Route('/{code}/edit', name: 'countries_edit_form', methods: ['GET'])]
    public function editForm(string $code): Response
    {
        error_log("DEBUG: CountryPagesController::editForm() called with code: " . $code);
        try {
            // Получить страну по коду
            $country = $this->countryScenarios->get($code);
            error_log("DEBUG: CountryPagesController::editForm() fetched country: " . json_encode($country));
            $title = "Edit Country: " . $country->shortName;
            $action = 'edit';
            $originalCountry = $country; 
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
            include __DIR__ . '/../../public/views/countries_form.php'; 
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
        $submittedToken = $request->request->get('_token');
        // if (!hash_equals($expectedToken, $submittedToken)) {
        //     throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        // }

        $data = $request->request->all();

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
            // Вызвать метод модели для обновления
            $this->countryScenarios->edit($code, $country);
            $flash_message = 'Country updated successfully!';
            $flash_type = 'success';

            $countries = $this->countryScenarios->getAll();
            $title = 'Countries List';
            $content = '';

            ob_start();
            $template_vars = [
                'countries' => $countries,
                'title' => $title,
                'content' => $content,
                'flash_message' => $flash_message,
                'flash_type' => $flash_type
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_list.php'; 
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);

        } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
            throw $this->createNotFoundException('Country not found');
        } catch (\App\Model\Exceptions\InvalidCodeException $e) {
            throw $this->createNotFoundException('Invalid country code');
        } catch (\Exception $e) {
            // Обработка других ошибок (валидация, дубликат)
            try {
                $originalCountry = $this->countryScenarios->get($code); 
            } catch (\App\Model\Exceptions\CountryNotFoundException $e) {
                 throw $this->createNotFoundException('Country not found');
            }
            $error = $e->getMessage();
            $title = "Edit Country: " . $originalCountry->shortName;
            $action = 'edit';

            ob_start();
            $template_vars = [
                'country' => $country, 
                'originalCountry' => $originalCountry,
                'action' => $action,
                'title' => $title,
                'error' => $error
            ];
            extract($template_vars);
            include __DIR__ . '/../../public/views/countries_form.php'; 
            $page_content = ob_get_clean();

            return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
        }
    }

    // Обработчик для удаления страны
    #[Route('/{code}/delete', name: 'countries_delete', methods: ['POST'])] 
    public function delete(string $code, Request $request): Response 
    {
        $submittedToken = $request->request->get('_token'); 
        // if (!hash_equals($expectedToken, $submittedToken)) {
        //     throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        // }
        try {
            $this->countryScenarios->delete($code); 
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
        
        ob_start();
        $template_vars = [
            'countries' => $countries,
            'title' => $title,
            'content' => $content,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type
        ];
        extract($template_vars);
        include __DIR__ . '/../../public/views/countries_list.php';
        $page_content = ob_get_clean();

        return new Response(content: $page_content, headers: ['Content-Type' => 'text/html']);
    }
}