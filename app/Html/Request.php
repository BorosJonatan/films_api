<?php

namespace App\Html;

use App\Repositories\BaseRepository;
use App\Repositories\FilmRepository;
use App\Repositories\ActorRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\DIrectorRepository;
use Directory;
use PhpParser\Node\Expr\Instanceof_;

class Request
{
    static array $acceptedRoutes = [
        'POST' => [
            '/users/login',
            '/users/logout',
            '/films',
            '/actors',
        ],
        'GET' => [
            '/films',
            '/actors',
            '/films/{id}',
            '/actors/{id}',
            '/films/{id}/actors',
            '/actors/{id}/films',
            '/categories',
            '/directors',
        ],
        'PUT' => [
            '/films/{id}',
            '/actors/{id}',
        ],
        'DELETE' => [
            '/films/{id}',
            '/actors/{id}',
        ],
    ];
    static function handle()
    {
        // Lekérjük a HTTP metódust és az URI-t
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Ellenőrizzük, hogy a kérés engedélyezett route-ra mutat-e
        if (!self::isRouteAllowed($requestMethod, $requestUri, self::$acceptedRoutes)) {
            return Response::response(['error' => 'Route not allowed'], 400);
        }

        // Feldolgozzuk az URI-t és az adatokat
        $requestData = self::getRequestData();
        $arrUri = self::requestUriToArray($_SERVER['REQUEST_URI']);
        $resourceName = self::getResourceName($arrUri);
        $resourceId = self::getResourceId($arrUri);
        $childResourceName = self::getChildResourceName($arrUri);

        // A metódus alapján meghívjuk a megfelelő függvényt
        switch ($requestMethod){
            case "POST":
                self::postRequest($resourceName, $requestData);
                break;
            case "PUT":
                self::putRequest($resourceName, $resourceId, $requestData);
                break;
            case "GET":
                self::getRequest($resourceName, $resourceId, $childResourceName);
                break;
            case "DELETE":
                self::deleteRequest($resourceName, $resourceId);
                break;
            default:
                echo 'Unknown request type';
                break;
        }
    }

    private static function getRequestData(): ?array
    {
        return json_decode(file_get_contents("php://input"), true);
    }

    private static function requestUriToArray($uri): array
    {
        $arrUri = explode("/", trim($uri, "/"));
        return [
            'resourceName' => $arrUri[0] ?? null,
            'resourceId' => !empty($arrUri[1]) ? (int)$arrUri[1] :  null,
            'childResourceName' => $arrUri[2] ?? null,
        ];
    }

    private static function getResourceName($arrUri){
        return $arrUri['resourceName'];
    }
    private static function getResourceId($arrUri){
        return $arrUri['resourceId'];
    }
    private static function getChildResourceName($arrUri){
        return $arrUri['childResourceName'];
    }

    private static function isRouteMatch($route, $uri): bool
    {
        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        foreach ($routeParts as $index => $routePart) {
            if (preg_match('/^{.*}$/', $routePart)) {
                continue; // Paraméter placeholder, bármilyen értéket elfogad
            }
            if ($routePart !== $uriParts[$index]) {
                return false;
            }
        }

        return true;
    }

    private static function isRouteAllowed($method, $uri, $routes): bool
    {
        if (!isset($routes[$method])) {
            return false;
        }

        foreach ($routes[$method] as $route) {
            if (self::isRouteMatch($route, $uri)) {
                return true;
            }
        }

        return false;
    }

    private static function getRepository($resourceName): ?BaseRepository
    {
        switch ($resourceName) {
            case 'films':
                return new FilmRepository();
            case 'actors':
                return new ActorRepository();
            case 'categories':
                return new CategoryRepository();
        
            case 'directors':
                return new DirectorRepository();
            default:
                return null;
        }
    }


    private static function postRequest($resourceName, $requestData)
    {
        $repository = self::getRepository($resourceName);
        if (!$repository) {
            return Response::json()
            ->withData([])
            ->withStatus(400)
            ->send();
        }

        $newId = $repository->create($requestData);
        $entity = $repository->find($newId);
        $code = 400; // Bad Request alapértelmezés
        if ($newId) {
            $code = 201; // Created
        }

        //Response::response(['id' => $newId], $code);
        Response::json()
        ->withData(['entity' => $entity])
        ->withStatus($code)
        ->send();
    }

    private static function deleteRequest($resourceName, $resourceId)
    {
        $repository = self::getRepository($resourceName);
        $result = $repository->delete($resourceId);
        if ($result) {
            $code = 204;
        }
        //Response::response([], $code);
        Response::json()
        ->withStatus($code)
        ->send();
    }

    private static function getRequest($resourceName, $resourceId = null, $childResourceName)
    {
        $path = parse_url($resourceName, PHP_URL_PATH); 

        // 2. Get the filename from that path
        $resourceName = basename($path);
        if ($childResourceName) {
            $repository = self::getRepository($childResourceName);
            if ($resourceId) {
                // Példa: /films/{id}/actors
                if ($childResourceName === 'films') {
                    if ($repository instanceof FilmRepository){
                        $films = $repository->getByFilm($resourceId);
                        Response::json()
                        ->withData(['films' => $films])
                        ->withStatus(200)
                        ->send();
                        return;
                    }
                }
                if ($childResourceName === 'actors') {
                    if ($repository instanceof ActorRepository){
                        $actors = $repository->getByActor($resourceId);
                        Response::json()
                        ->withData(['actors' => $actors])
                        ->withStatus(200)
                        ->send();
                        return;
                    }
                }
            }
        }
        
        $repository = self::getRepository($resourceName);
        if ($resourceId) {
            $entity = $repository->find($resourceId);
            if (!$entity) {
                Response::json()
                ->withData(['entity' => $entity])
                ->withStatus(404)
                ->send();
                return;
            }
            Response::json()
            ->withData(['entity' => $entity])
            ->withStatus(200)
            ->send();
            return;
        }

        $needle = $_GET['needle'] ?? null;
        
        if ($needle){
            $entities = $repository->getAll($needle);
            Response::json()
            ->withData(['entities' => $entities])
            ->withStatus(200)
            ->send();
            exit;
        }

        $entities = $repository->getAll();
        Response::json()
        ->withData(['entities' => $entities])
        ->withStatus(200)
        ->send();
    }
    private static function putRequest($resourceName, $resourceId, $requestData)
    {
        $repository = self::getRepository($resourceName);
        $code = 404;
        $entity = $repository->find($resourceId);
        if ($entity) {
            $data = [];
            foreach ($requestData as $key => $value) {
                $data[$key] = $value;
            }
            $result = $repository->update($resourceId, $data);
            if ($result) {
                $code = 202;
            }
        }
        Response::json()
        ->withData(['entity' => $entity])
        ->withStatus($code)
        ->send();
    }
}

/**
 * @api {post} /counties Create a new county
 * @apiName CreateCounty
 * @apiGroup Counties
 * @apiVersion 1.0.0
 *
 * @apiParam {String} name County name
 *
 * @apiSuccess {Object} data Created county
 * @apiSuccess {Number} data.id County ID
 * @apiSuccess {String} data.name County name
 *
 * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 201 Created
 *   {
 *     "data": {
 *      "id": 2,
 *       "name": "Borsod-Abaúj-Zemplén"
 *     }
 *   }
 */


/**
 * @api {get} /counties Get all counties
 * @apiName getCounties
 * @apiGroup Counties
 * @apiVersion 1.0.0
 * 
 * @apiSuccess {Object[]} counties    List of Counties.
 * @apiSuccess {Number} counties.id         County unique ID.
 * @apiSuccess {String} counties.name       County Name.
 * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 200 OK
 *   {
 *    "counties": [
 *         {"id": 2, "name": "Borsod-Abaúj-Zemplén"}
 *        {"id": 1, "name": "Pest"}
 *        .....
 *      ]    
 *   }
 */

    /**
 * @api {get} /counties/:id Get county by id
 * @apiName GetCountyById
 * @apiGroup Counties
 * @apiVersion 1.0.0
 *
 * @apiParam {Number} id County unique ID
 *
 * @apiSuccess {Object} data County object
 * @apiSuccess {Number} data.id County ID
 * @apiSuccess {String} data.name County name
 *  * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 200 OK
 *   {
 *     "data": [
 *       { "id": 1, "name": "Miskolc" },
 *     ]
 *   }
 */

 /**
 * @api {get} /counties/:countyId/cities Get cities by county
 * @apiName GetCitiesByCounty
 * @apiGroup Cities
 * @apiVersion 1.0.0
 *
 * @apiParam {Number} countyId County unique ID
 *
 * @apiSuccess {Object[]} data List of cities
 * @apiSuccess {Number} data.id City ID
 * @apiSuccess {String} data.name City name
 *
 * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 200 OK
 *   {
 *     "data": [
 *       { "id": 1, "name": "Miskolc" },
 *       { "id": 2, "name": "Kazincbarcika" }
 *       .....
 *     ]
 *   }
 */
/**
 * @api {get} /counties/:countyId/cities/:cityId Get city by county and city id
 * @apiName GetCityByCounty
 * @apiGroup Cities
 * @apiVersion 1.0.0
 *
 * @apiParam {Number} countyId County unique ID
 * @apiParam {Number} cityId City unique ID
 *
 * @apiSuccess {Object} data City object
 * @apiSuccess {Number} data.id City ID
 * @apiSuccess {String} data.name City name
 *
 * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 200 OK
 *   {
 *     "data": {
 *       "id": 1,
 *       "name": "Miskolc"
 *     }
 *   }
 */
/**
 * @api {get} /counties/:countyId/cities/ Create new city in a county
 * @apiName CreateCity
 * @apiGroup Cities
 * @apiVersion 1.0.0
 *
 * @apiParam {Number} countyId County unique ID
 * @apiParam {String} cityName City name
 *
 * @apiSuccess {Object} data City object
 * @apiSuccess {String} data.name City name
 *
 * @apiSuccessExample {json} Success-Response:
 *   HTTP/1.1 200 OK
 *   {
 *     "data": {
 *       "county_id": 1,
 *       "id": 3,
 *       "city": "Budapest"
 *     }
 *   }
 */