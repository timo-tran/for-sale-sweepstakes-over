<?php

namespace Restomods\ListingBundle\Controller;

use Restomods\ListingBundle\Entity\Sweepstakes;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SweepstakesAdminController extends CRUDController
{
	/**
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function activateAction( Request $request )
	{
		// /** @var Listing $listing */


		$em = $this->getDoctrine()->getManager();
		$sweepstakes= $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => true));
		if ($sweepstakes) {
			$sweepstakes->setActive(false);
			$em->flush();
		}

		$sweepstakes = $this->admin->getSubject();
		$sweepstakes->setActive( true );
		$this->admin->update( $sweepstakes );

		$this->addFlash( 'sonata_flash_success', "Sweepstakes activated" );

		return new RedirectResponse( $this->admin->generateUrl( 'list' ) );
	}

	/**
     * @param $id
     */
    public function cloneAction($id)
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
        }

        // Be careful, you may need to overload the __clone method of your object
        // to set its id to null !
        $clonedObject = clone $object;
		$clonedObject->setName($object->getName().' (Clone)');

		// copy images
		$images = $object->getImages();
		if (!empty($images)) {
			$images_new = array();
			foreach($images as $image) {
				$image_new = clone $image;
				$file_original = $image_new->getSrc();
				if (!empty($file_original)) {
					$file_new = $this->copyImage($file_original);
					if ($file_new !== false) {
						$image_new->setSrc($file_new);
					} else {
						$image_new->setSrc('');
					}
				}
				$images_new[] = $image_new;
			}
			$clonedObject->setImages($images_new);
		}

		// copy featured images
		$featuredImage1 = $object->getFeaturedImage1();
		if (!empty($featuredImage1)) {
			$image = $this->copyImage($featuredImage1);
			if ($image !== false) {
				$clonedObject->setFeaturedImage1($image);
			} else {
				$clonedObject->setFeaturedImage1('');
			}
		}

		$featuredImage2 = $object->getFeaturedImage2();
		if (!empty($featuredImage2)) {
			$image = $this->copyImage($featuredImage2);
			if ($image !== false) {
				$clonedObject->setFeaturedImage2($image);
			} else {
				$clonedObject->setFeaturedImage2('');
			}
		}

		// copy benefits section image
		$benefitsSectionImage = $object->getBenefitsSectionImage();
		if (!empty($benefitsSectionImage)) {
			$image = $this->copyImage($benefitsSectionImage);
			if ($image !== false) {
				$clonedObject->setBenefitsSectionImage($image);
			} else {
				$clonedObject->setBenefitsSectionImage('');
			}
		}

        $this->admin->create($clonedObject);

        $this->addFlash('sonata_flash_success', 'Cloned successfully');

        return new RedirectResponse($this->admin->generateUrl('list'));
    }

	protected function copyImage($filename) {
		$upload_dir      = $this->container->getParameter( 'upload_dir' );
		if (file_exists($upload_dir . '/'.$filename)) {
			$file_new = md5(time().uniqid()).'.'.pathinfo($filename, PATHINFO_EXTENSION);
			@copy($upload_dir . '/'.$filename, $upload_dir.'/'.$file_new);
			return $file_new;
		}
		return false;
	}
}
