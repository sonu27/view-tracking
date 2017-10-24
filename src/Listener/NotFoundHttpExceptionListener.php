<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundHttpExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof NotFoundHttpException) {
            $response = new Response('Not found', Response::HTTP_NOT_FOUND);

            $event->setResponse($response);
        }
    }
}
