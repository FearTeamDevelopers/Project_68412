<?php

use App\Etc\Controller;
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
        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object instanceof \App_Model_News) {
            if ($object->getMetaImage() != '') {
                $layoutView->set('metaogimage', "http://{$this->getServerHost()}{$object->getMetaImage()}");
            }

            $layoutView->set('metaogurl', "http://{$this->getServerHost()}/aktuality/r/" . $object->getUrlKey() . '/');
            $layoutView->set('metaogtype', 'article');
        } else {
            $layoutView->set('metaogurl', "http://{$this->getServerHost()}/" . $object->getUrlKey() . '/');
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
        preg_match_all('/\(\!(video|photo|read|gallery)_[0-9a-z]+[a-z_]*\!\)/', $content->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);
            
            if ($match == 'read_more' || $match == 'gallery') {
                $float = '';
                list($type, $id) = explode('_', $match);
            } else {
                list($type, $id, $float) = explode('_', $match);
            }

            $body = $content->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgMain', 'imgThumb')
                );
                
                if($float == 'left'){
                    $floatClass = 'class="left-10"';
                }elseif($float == 'right'){
                    $floatClass = 'class="right-10"';
                }else{
                    $floatClass = '';
                }

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" {$floatClass} height=\"200px\" alt=\"ZKO KnL\"/></a>";

                $body = str_replace("(!photo_{$id}_{$float}!)", $tag, $body);

                $content->$parsedField = $body;
            }
            
             if ($type == 'video') {
                $video = App_Model_Video::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('title', 'path', 'width', 'height')
                );

                $tag = "<iframe width=\"{$video->width}\" height=\"{$video->height}\" "
                        . "src=\"{$video->path}\" frameborder=\"0\" allowfullscreen></iframe>";

                $body = str_replace("(!video_{$id}!)", $tag, $body);
                $content->$parsedField = $body;
            }
            
            if($type == 'gallery'){
                $gallery = App_Model_Gallery::first(array('isPublic = ?' => true, 'id = ?' => $id));
                $tag = "<a href=\"/galerie/r/{$gallery->urlKey}\">{$gallery->title}</a>";
                $body = str_replace("(!gallery_{$id}!)", $tag, $body);
                $content->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"/aktuality/r/{$content->getUrlKey()}\" class=\"news-read-more\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $content->$parsedField = $body;
            }
        }

        return $content;
    }

    /**
     * 
     * @param type $page
     */
    public function index()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $config = Registry::get('configuration');

        $content = $this->getCache()->get('news-1');

        $npp = $config->news_per_page;
        
        if (NULL !== $content) {
            $news = $content;
        } else {
            $news = App_Model_News::all(
                            array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s')),
                        array('id', 'urlKey', 'author', 'title', 'shortBody', 'created', 'rank'),
                        array('rank' => 'desc', 'created' => 'DESC'), (int)$npp, 1);

            if ($news !== null) {
                foreach ($news as $_news) {
                    $this->_parseContentBody($_news, 'shortBody');
                }
                
                $this->getCache()->set('news-1', $news);
            } else {
                $news = array();
            }
        }
        
        $newsCount = App_Model_News::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );
        $newsPageCount = ceil($newsCount / $npp);

        $view->set('newsbatch', $news)
            ->set('newspagecount', $newsPageCount);
        
        
        $canonical = 'http://' . $this->getServerHost() . '/';
        
        $layoutView->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function members()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('clenove');

        if (NULL !== $content) {
            $members = $content;
        } else {
            $members = App_Model_User::fetchMembersWithDogs();
            $this->getCache()->set('clenove', $members);
        }

        $canonical = 'http://' . $this->getServerHost() . '/clenove';

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'ZKO - Členové');
        $view->set('members', $members);
    }

    /**
     * 
     */
    public function history()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('historie');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'historie'));
            $this->getCache()->set('historie', $content);
        }

        $parsed = $this->_parseContentBody($content);

        $canonical = 'http://' . $this->getServerHost() . '/historie';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'ZKO - Historie');
    }

    /**
     * 
     */
    public function actions()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('akce');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'akce'));
            $this->getCache()->set('akce', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $canonical = 'http://' . $this->getServerHost() . '/akce';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'ZKO - Akce');
    }

}
