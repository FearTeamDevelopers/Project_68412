<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_Gallery extends Controller
{

    /**
     * 
     */
    public function index($year = null)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $host = RequestMethods::server('HTTP_HOST');
        $cache = Registry::get('cache');
        
        if($year == null){
            $year = date('Y');
            $canonical = 'http://' . $host . '/gallerie';
        }else{
            $canonical = 'http://' . $host . '/gallerie/'.$year;
            
        }

        $content = $cache->get('galerie');

        if (NULL !== $content) {
            $galleries = $content;
        } else {
            $galleries = App_Model_Gallery::fetchGalleriesByYear($year);
            $cache->set('galerie', $galleries);
        }

        $view->set('galleries', $galleries);

        $layoutView->set('canonical', $canonical);
    }
    
    /**
     * 
     * @param type $id
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $host = RequestMethods::server('HTTP_HOST');
        
        $gallery = App_Model_Gallery::fetchActivePublicGalleryById((int)$id);
        $view->set('gallery', $gallery);

        $canonical = 'http://' . $host . '/galerie/r/'.$id;
        $layoutView->set('canonical', $canonical);
    }
}