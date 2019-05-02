<?php
namespace Restomods\ListingBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;

abstract class UploadAdmin extends AbstractAdmin
{
    /**
     * @return string
     */
    abstract public function getUploadField();

    public function getTemplate($name){
        if($name == "edit"){
            return "RestomodsListingBundle:Admin:Upload/media_edit.html.twig";
        }
        return parent::getTemplate($name);
    }

    protected function getImageUploadWidget($images)
    {
        $container = $this->getConfigurationPool()->getContainer();

        $widget = '<div id="image-uploader">';
        $images = $this->getSortedImages($images);
        if (count($images)) {

            foreach ($images as $i => $image) {
                $widget .= '<div class="image-wrapper col-lg-2 col-md-4 col-sm-6 col-xs-12">' .
                    '<div class="inner-wrapper">';
                $widget .= '<div class="preview" style="background-image: url(' . $container->get('liip_imagine.cache.manager')->getBrowserPath($image['src'], 'thumb') . ')"></div>'.
                    '<div class="actions">'.
                    '<span class="delete delete-image"><i class="fa fa-trash-o"></i></span>'.

                    '</div>';
                $widget .= '<div class="fields" data-delete="' . $this->getUniqid() . '['.$this->getUploadField().'][' . $i . '][_delete]">' .
                    '<input class="image-src" type="hidden" id="' . $this->getUniqid() . '_'.$this->getUploadField().'_' . $i . '_src" name="' . $this->getUniqid() . '['.$this->getUploadField().'][' . $i . '][src]" required="required" maxlength="255" class=" form-control" value="' . $image['src'] . '">' .
                    '<input class="image-position" type="hidden" id="' . $this->getUniqid() . '_'.$this->getUploadField().'_' . $i . '_position" name="' . $this->getUniqid() . '['.$this->getUploadField().'][' . $i . '][position]" value="' . $image['position'] . '" />' .
                    '</div>';
                $widget .= '</div>';
                $widget .= '</div>';
            }
        }
        $widget .= '<div class="upload-widget col-lg-2 col-md-4 col-sm-6 col-xs-12">' .
            '<div class="inner">' .
            '<forms multipart="false" id="upload-handler"><input type="file" name="files[]" multiple></forms>' .
            '</div>' .
            '<input type="button" value="upload" id="start-upload-file" />' .
            '</div>';

        $widget .= '</div>';

        return $widget;

    }

    protected function getSortedImages($images)
    {
        $image_group = [];
        if (count($images) > 0) {
            foreach ($images as $image) {
                $image_group[] = array(
                    'src' => $image->getSrc(),
                    'position' => $image->getPosition(),
                );
            }
        }
        $imgs = array();
        foreach ($image_group as $key => $row) {
            $imgs[$key] = $row['position'];
        }
        array_multisort($imgs, SORT_ASC, $image_group);

        return $image_group;
    }
}