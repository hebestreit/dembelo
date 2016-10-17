<?php

/* Copyright (C) 2015 Michael Giesler
 *
 * This file is part of Dembelo.
 *
 * Dembelo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dembelo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License 3 for more details.
 *
 * You should have received a copy of the GNU Affero General Public License 3
 * along with Dembelo. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * @package DembeloMain
 */

namespace DembeloMain\Controller;

use DembeloMain\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 */
class UserController extends Controller
{
    /**
     * @Route("/login", name="login_route")
     *
     * @return string
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $user->setEmail($lastUsername);

        $form = $this->createFormBuilder($user)
            ->setAction($this->generateUrl('login_check'))
            ->add('_username', FormType\EmailType::class, array('label' => 'Email'))
            ->add('password', FormType\PasswordType::class, array('label' => 'Password'))
            ->add('save', FormType\SubmitType::class, array('label' => 'Login', 'attr' => array('class' => 'btn btn-primary')))
            ->getForm();


        return $this->render(
            'DembeloMain::user/login.html.twig',
            array(
                'error' => $error,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @Route("/login_check", name="login_check")
     *
     * @return string
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/registration", name="register")
     *
     * @param Request $request request object
     *
     * @return string
     */
    public function registrationAction(Request $request)
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setStatus(0);
        $form = $this->createFormBuilder($user)
            ->add('email', FormType\EmailType::class)
            ->add('password', FormType\PasswordType::class, array('label' => 'Password'))
            ->add('gender', FormType\ChoiceType::class, array(
                'choices'  => array('male' => 'm', 'female' => 'f'),
                'label' => 'Gender',
                'required' => false,
            ))
            ->add('source', FormType\TextType::class, array('label' => 'Where have you first heard of Dembelo?', 'required' => false))
            ->add('reason', FormType\TextareaType::class, array('label' => 'Why to you want to participate in our Closed Beta?', 'required' => false))
            ->add('save', FormType\SubmitType::class, array('label' => 'Request registration'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $mongo = $this->get('doctrine_mongodb');
            $dm = $mongo->getManager();
            $encoder = $this->get('security.password_encoder');
            $password = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            $user->setMetadata('created', time());
            $user->setMetadata('updated', time());
            $dm->persist($user);
            $dm->flush();

            return $this->redirectToRoute('registration_success');
        }

        return $this->render(
            'DembeloMain::user/register.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/registrationSuccess", name="registration_success")
     *
     * @return string
     */
    public function registrationsuccessAction()
    {
        return $this->render(
            'DembeloMain::user/registrationSuccess.html.twig',
            array()
        );
    }

    /**
     * @Route("/activation/{hash}", name="emailactivation")
     *
     * @param string $hash activation hash
     *
     * @return string
     */
    public function activateemailAction($hash)
    {
        $mongo = $this->get('doctrine_mongodb');
        $repository = $mongo->getRepository('DembeloMain:User');
        $dm = $mongo->getManager();
        $user = $repository->findOneByActivationHash($hash);
        $user->setActivationHash('');
        $user->setStatus(1);
        $dm->persist($user);
        $dm->flush();

        return $this->render(
            'DembeloMain::user/activationSuccess.html.twig',
            array()
        );
    }
}
