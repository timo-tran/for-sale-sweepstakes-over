<?php
namespace Restomods\ListingBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController;

class SweepstakesUserEntriesAdminController extends CRUDController
{
    public function listAction()
    {
        $request = $this->getRequest();
        $this->admin->checkAccess('list');
        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findAll();
        $sweepstakesUserEntries = null;
        $sweepstakes_id = $request->get('sweepstakes_data');
        if($sweepstakes){
            $activeSweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => true));
            $sweepstakes_id = $sweepstakes_id ? $sweepstakes_id : ($activeSweepstakes ? $activeSweepstakes->getId() : ($sweepstakes[0]->getId()));
            $sweepstakesUserEntries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->getSweepstakesUserEntries($sweepstakes_id);
        }
        return $this->render($this->admin->getTemplate('list'), array(
            'action' => 'list',
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'sweepstakesUserEntries' => $sweepstakesUserEntries,
            'sweepstakes' => $sweepstakes,
            'sweepstakes_id' => $sweepstakes_id,
        ), null);
    }
}