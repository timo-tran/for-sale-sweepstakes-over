<?php

namespace Application\Sonata\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Restomods\ListingBundle\Entity\UserReferrer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Sonata\UserBundle\Controller\RegistrationFOSUser1Controller as BaseRegistrationController;

class RegistrationFOSUser1Controller extends BaseRegistrationController
{
    /**
     * @return Response
     *
     * @throws AccessDeniedException
     */
    public function registerAction($code = null)
    {
        if($code){
            $referrer = $this->getDoctrine()->getManager()->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('referrerCode' => $code));
            if($referrer){
                $this->get('session')->set('referrerCode', $code);
                return $this->redirectToRoute('restomods_sweepstakes');
            }
        }

        $user = $this->getUser();

        if ($user instanceof UserInterface) {
            $this->get('session')->getFlashBag()->set('sonata_user_error', 'sonata_user_already_authenticated');

            return $this->redirect($this->generateUrl('sonata_user_profile_show'));
        }

        $form = $this->get('sonata.user.registration.form');
        $formHandler = $this->get('sonata.user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($confirmationEnabled);
        if ($process) {
            $user = $form->getData();
            $em = $this->getDoctrine()->getManager();
            if($code){
                $userList = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('referrerCode' => $code));
                if($userList){
                    $userReferrer = new UserReferrer();
                    $userReferrer->setReferBy($userList);
                    $userReferrer->setSignUp($user);
                    $em->persist($userReferrer);
                    $em->flush();
                }
            }

            $authUser = false;
            if ($confirmationEnabled) {
                $this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $url = $this->generateUrl('fos_user_registration_check_email');
            } else {
                $authUser = true;
                $route = $this->get('session')->get('sonata_basket_delivery_redirect');

                if (null !== $route) {
                    // NEXT_MAJOR: remove the if block
                    @trigger_error(<<<'EOT'
Setting a redirect url in the sonata_basket_delivery_redirect session variable
is deprecated since 3.2 and will no longer result in a redirection to this url in 4.0.
EOT
                        , E_USER_DEPRECATED);
                    $this->get('session')->remove('sonata_basket_delivery_redirect');
                    $url = $this->generateUrl($route);
                } else {
                    $this->get('session')->set('modal',true);
                    $url = $this->get('session')->get('sonata_user_redirect_url');
                }
            }

            if($code){
                $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
                $url = $this->generateUrl($settings->getReferralLandingPage());
            }
            else{
                $url = $this->generateUrl('sonata_user_profile_show');
            }

            $this->setFlash('fos_user_success', 'registration.flash.user_created');

            $response = $this->redirect($url);

            if ($authUser) {
                $this->authenticateUser($user, $response);
            }

            return $response;
        }

        // NEXT_MAJOR: Inject $request in the method signature instead.
        if ($this->has('request_stack')) {
            $request = $this->get('request_stack')->getCurrentRequest();
        } else {
            $request = $this->get('request');
        }

        $this->get('session')->set('sonata_user_redirect_url', $request->headers->get('referer'));

        return $this->render('FOSUserBundle:Registration:register.html.'.$this->getEngine(), array(
            'form' => $form->createView(),
        ));
    }

}
