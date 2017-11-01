<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/login", name="login")
     * @Method("POST")
     */
    public function loginAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);

        $json = $request->get('json', null);

        $data = array(
            'status' => 'error',
            'data' => 'Send data via post!!'
        );

        if ($json != null) {
            //hacemos login


            //Convertimos el json a un objeto php
            $params = json_decode($json);

            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getHash = (isset($params->getHash)) ? $params->getHash : false;

            $emailConstraint = new Email();
            $emailConstraint->message = 'Email no válido';
            $validate_email = $this->get('validator')->validate($email, $emailConstraint);

            $pwd = hash('sha256', $password);

            if ($email != null && count($validate_email) == 0 && $password != null) {
                $jwt_auth = $this->get(JwtAuth::class);

                $signup = $jwt_auth->signup($email, $pwd, $getHash);

                $data = $this->json($signup);
            } else {
                $data = array(
                    'status' => 'error',
                    'data' => 'Email or password incorrect'
                );
            }
        }
        return $helpers->json($data);
    }

    /**
     * @Route("/prueba", name="prueba")
     */
    public function pruebaAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);
        $token = $request->get('authorization', null);

        if ($token && $jwt_auth->checkToken($token)) {
            $em = $this->getDoctrine()->getManager();
            $userRepo = $em->getRepository('AppBundle:Users');
            $users = $userRepo->findAll();

            return $helpers->json(array(
                'status' => 'success',
                'users' => $users
            ));
        } else {
            return $helpers->json(array(
                'status' => 'error',
                'code' => 400,
                'data' => 'Authorization no valid!!'
            ));
        }
    }


}
