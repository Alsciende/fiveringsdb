<?php

namespace AppBundle\Controller\API;

use AppBundle\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Description of ApiController
 *
 * @author Alsciende <alsciende@icloud.com>
 */
abstract class BaseApiController extends Controller
{
    public function success($data = null)
    {
        /* @var $service ApiService */
        $service = $this->get('app.api');
        return $service->buildResponse($data);
    }
    
    public function failure($message = "unknown_error", $description = "An unknown error has occured.")
    {
        $this->get('logger')->info($message);
        return new \Symfony\Component\HttpFoundation\JsonResponse([
            "success" => FALSE,
            "message" => $message,
            "description" => $description,
        ]);
    }
    
}
