<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_Index extends Controller
{
    
    /**
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, \App_Model_PageContent $object)
    {
        $host = RequestMethods::server('HTTP_HOST');

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $layoutView->set('metaogimage', "http://{$host}/public/images/meta_image.jpg");
        $layoutView->set('metaogurl', "http://{$host}/" . $object->getUrlKey() . '/');

        $layoutView->set('metaogtype', 'website');

        return;
    }

    /**
     * Method replace specific strings whit their equivalent images
     * @param \App_Model_PageContent $news
     */
    private function _parseContentBody(\App_Model_PageContent $content, $parsedField = 'body')
    {
        preg_match_all('/\(\!(photo|read)_[0-9a-z]+\!\)/', $content->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);
            list($type, $id) = explode('_', $match);

            $body = $content->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgMain', 'imgThumb')
                );

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" height=\"250px\" alt=\"Karneval\"/></a>";

                $body = str_replace("(!photo_{$id}!)", $tag, $body);

                $content->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"#\" class=\"ajaxLink news-read-more\" "
                        . "id=\"show_news-detail_{$content->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $content->$parsedField = $body;
            }
        }

        return $content;
    }
    
    /**
     * 
     */
    public function index()
    {

    }
    
    /**
     * 
     */
    public function members()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        
        $members = App_Model_User::fetchMembersWithDogs();
        
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/clenove';
        
        $layoutView->set('canonical', $canonical);
        $view->set('members', $members);
    }
    
    /**
     * 
     */
    public function history()
    {
        
    }
     public function akce()
    {
        
    }
    
    /**
     * 
     */
    public function gallery()
         {
        $view = $this->getActionView();
        
        $gallery = App_Model_Gallery::all();
        
        
        $view->set('gallery', $gallery);
    }
    
}
