<?php

use App\Etc\Controller;

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

        if ($year == null) {
            $year = date('Y');
            $canonical = 'http://' . $this->getServerHost() . '/gallerie';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/gallerie/' . $year;
        }

        $content = $this->getCache()->get('gallery-' . $year);
        $cachedYears = $this->getCache()->get('gallery-years');

        if ($content !== null) {
            $galleries = $content;
        } else {
            $galleries = App_Model_Gallery::fetchGalleriesByYear($year);
            $this->getCache()->set('gallery-' . $year, $galleries);
        }

        if ($cachedYears !== null) {
            $returnYears = $cachedYears;
        } else {
            $galleryYears = App_Model_Gallery::all(
                            array('showDate <> ?' => ''), 
                    array('DISTINCT(EXTRACT(YEAR FROM showDate))' => 'year'), 
                    array('year' => 'ASC')
            );

            $returnYears = array();

            foreach ($galleryYears as $galyear) {
                $returnYears[] = $galyear->getYear();
            }
            
            $this->getCache()->set('gallery-years', $returnYears);
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

        $gallery = App_Model_Gallery::fetchActivePublicGalleryByUrlkey($urlkey);
        
        if($gallery !== null){
            $canonical = 'http://' . $this->getServerHost() . '/galerie/r/' . $urlkey;
            $layoutView->set('canonical', $canonical)
                    ->set('metatitle', $gallery->getTitle());
        }
        
        $view->set('gallery', $gallery);
    }

}
