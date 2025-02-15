<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\WebAuthenticator;
use App\Service\EmailService;
use App\Service\UploadService;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request, 
        UserPasswordEncoderInterface $passwordEncoder, 
        GuardAuthenticatorHandler $guardHandler, 
        WebAuthenticator $authenticator,
        EmailService $emailService,
        Encryptor $encryptor,
        UploadService $uploadService
        ): Response
    {
        //dd($encryptor->encrypt('emmeline'));
        //dd($encryptor->decrypt('ULUOynN2ex6z2vJ4nwWRnbHDrABzWwCh'));
        if ($this->getUser()) {
            return $this->redirectToRoute('login');
            
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $image = $form->get('image')->getData();
            if ($image) {
                $fileName = $uploadService->uploadImage($image, $user);
                $user->setImage($fileName);
            }

            $user->setRoles(['ROLE_MEMBER']);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            
            //Envoi mail
            $emailService->send([                
                'to' => '$user->getEmail', //if empty => adminEmail
                'subject' => 'Validez votre inscription',
                'template' => 'email/verify_email.html.twig',
                'context' => [
                    'user' => $user
                ],
            ]);

            $this->addFlash("success", "Vous êtes bien enregistré ! Merci de vérifier votre compte en cliquant sur le lien que nous vous avons envoyé dans le mail.");
            return $this->redirectToRoute('app_login');
            // return $guardHandler->authenticateUserAndHandleSuccess(
            //     $user,
            //     $request,
            //     $authenticator,
            //     'main' // firewall name in security.yaml
            // );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verification-email/{token}", name="verify_email")
     */
    public function verifyEmail(
        string $token, 
        Encryptor $encryptor, 
        UserRepository $userRepository,
        GuardAuthenticatorHandler $guardHandler,
        WebAuthenticator $authenticator,
        Request $request)
    {
        $id = $encryptor->decrypt($token);
        $user =$userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Votre compte n'a pas été trouvé");
        }

        $user->setVerifiedEmail(true);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $authenticator,
            'main'
        );

        
    }
}