<?php

namespace Helpers;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Controller\Controller;
use Siler\Http\Request;
use Siler\GraphQL as SilerGraphQL;
use Siler\Http\Response;
use Concrete\Core\Support\Facade\Application as App;
use Symfony\Component\HttpFoundation\JsonResponse;

class Api extends Controller
{
    public function view()
    {
        if (Request\method_is('post')) {
            Response\cors();
            $user = null;

            $tokenHelper = App::make(\Helpers\Token::class);
            $token = $tokenHelper->getToken();
            if ($token) {
                try {
                    $authorize = App::make(\Helpers\Authorize::class);
                    $user = $authorize->authenticated($token);

                    if ($user) {
                        $authenticate = App::make(\Helpers\Authenticate::class);
                        $authenticate->logRequest($user);
                    }
                } catch (\Exception $e) {
                    return new JsonResponse($e, 401);
                }
            }

            if (Request\header('Content-Type') == 'application/json') {
                $data = Request\json('php://input');
            } else {
                $data = Request\post();
            }

            if (!is_array($data)) {
                throw new \UnexpectedValueException('Input should be a JSON object');
            }

            $schema = \Concrete5GraphqlWebsocket\SchemaBuilder::get();
            SilerGraphQL\init($schema, null, ['user' => $user]);
        }
    }
}
