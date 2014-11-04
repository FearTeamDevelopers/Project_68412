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
     * @param type $year
     */
    public function index($year = null)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $host = RequestMethods::server('HTTP_HOST');
        $cache = Registry::get('cache');

        if ($year == null) {
            $year = date('Y');
            $canonical = 'http://' . $host . '/gallerie';
        } else {
            $canonical = 'http://' . $host . '/gallerie/' . $year;
        }

        $content = $cache->get('galerie');

        if (NULL !== $content) {
            $galleries = $content;
        } else {
            $galleries = App_Model_Gallery::fetchGalleriesByYear($year);
            $cache->set('galerie', $galleries);
        }

        $galleryYears = App_Model_Gallery::all(
                    array('showDate <> ?' => ''), 
                    array('DISTINCT(EXTRACT(YEAR FROM showDate))' => 'year'), 
                    array('year' => 'ASC')
        );

        $returnYears = array();

        foreach ($galleryYears as $galyear) {
            $returnYears[] = $galyear->getYear();
        }
        $view->set('galleries', $galleries)
                ->set('years', $returnYears);

        $layoutView->set('canonical', $canonical)
            ->set('metatitle', 'ZKO - Galerie '.$year);
    }

    /**
     * 
     * @param type $urlkey
     */
    public function detail($urlkey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $host = RequestMethods::server('HTTP_HOST');

        $gallery = App_Model_Gallery::fetchActivePublicGalleryByUrlkey($urlkey);
        
        if($gallery !== null){
            $canonical = 'http://' . $host . '/galerie/r/' . $urlkey;
            $layoutView->set('canonical', $canonical)
                    ->set('metatitle', $gallery->getTitle());
        }
        
        $view->set('gallery', $gallery);
    }

}
