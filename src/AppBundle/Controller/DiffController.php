<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DiffController
 * @package AppBundle\Controller
 */
class DiffController extends Controller
{
    /**
     * @Route("/diff/{engine}", name="post_diff")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string $engine
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postDiffAction(Request $request, string $engine)
    {
        $json = json_decode($request->getContent(), true);

        $old = $json['htmlOld'];
        $new = $json['htmlNew'];

        $dispatcher = $this->get('app.diff.dispatcher');

        $diff = $dispatcher->getHtmlDiff($engine, $old, $new);

        return new JsonResponse([
            'htmlDiff' => $diff,
        ]);
    }
}
