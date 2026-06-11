<?php

namespace App\Controller;

use Diff\DiffDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DiffController
{
    public function __construct(private DiffDispatcher $dispatcher) {}

    #[Route('/diff/{engine}', methods: ['POST'])]
    public function diff(Request $request, string $engine): JsonResponse
    {
        $json = json_decode($request->getContent(), true);

        $diff = $this->dispatcher->getHtmlDiff($engine, $json['htmlOld'], $json['htmlNew']);

        return new JsonResponse(['htmlDiff' => $diff]);
    }
}
