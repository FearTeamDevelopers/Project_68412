<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Model\Model;

/**
 * 
 */
class App_Controller_Index extends Controller
{

    /**
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, Model $object)
    {
        $host = RequestMethods::server('HTTP_HOST');

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object instanceof \App_Model_News) {
            if ($object->getMetaImage() != '') {
                $layoutView->set('metaogimage', "http://{$host}/public/images/meta_image.jpg");
            }

            $layoutView->set('metaogurl', "http://{$host}/aktuality/r/" . $object->getUrlKey() . '/');
            $layoutView->set('metaogtype', 'article');
        } else {
            $layoutView->set('metaogimage', "http://{$host}/public/images/meta_image.jpg");
            $layoutView->set('metaogurl', "http://{$host}/" . $object->getUrlKey() . '/');

            $layoutView->set('metaogtype', 'website');
        }


        return;
    }

    /**
     * Method replace specific strings whit their equivalent images
     * @param \App_Model_PageContent $news
     */
    private function _parseContentBody(Model $content, $parsedField = 'body')
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
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $cache = Registry::get('cache');

        $content = $cache->get('aktuality-all');

        if (NULL !== $content) {
            $news = $content;
        } else {
            $npp = (int) $this->loadConfigFromDb('news_per_page');

            $news = App_Model_News::all(
                            array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s')),
                        array('id', 'urlKey', 'author', 'title', 'shortBody', 'created', 'rank'),
                        array('rank' => 'asc', 'created' => 'DESC'), $npp, (int) $page);

            if ($news !== null) {
                foreach ($news as $_news) {
                    $this->_parseNewsBody($_news, 'shortBody');
                }
                
                $cache->set('aktuality-all', $news);
            } else {
                $news = array();
            }
        }

        $view->set('newsbatch', $news);
    }

    /**
     * 
     */
    public function members()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('clenove');

        if (NULL !== $content) {
            $members = $content;
        } else {
            $members = App_Model_User::fetchMembersWithDogs();
            $cache->set('clenove', $members);
        }


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
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('historie');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'historie'));
            $cache->set('historie', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/historie';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function actions()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('akce');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'akce'));
            $cache->set('akce', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/akce';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function gallery($year = null)
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

}
