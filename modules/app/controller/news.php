<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_News extends Controller
{

    /**
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, \App_Model_News $object)
    {
        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object->getMetaImage() != '') {
            $layoutView->set('metaogimage', "http://{$host}{$object->getMetaImage()}");
        }

        $layoutView->set('canonical', "http://{$this->getServerHost()}/aktuality/r/" . $object->getUrlKey() . '/')
                ->set('metaogurl', "http://{$this->getServerHost()}/aktuality/r/" . $object->getUrlKey() . '/')
                ->set('metaogtype', 'article');

        return;
    }

    /**
     * Method replace specific strings whit their equivalent images
     * 
     * @param \App_Model_News $content
     * @param type $parsedField
     * @return \App_Model_News
     */
    private function _parseNewsBody(\App_Model_News $content, $parsedField = 'body')
    {
        preg_match_all('/\(\!(photo|read|gallery)_[0-9a-z]+[a-z_]*\!\)/', $content->$parsedField, $matches);
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

                if ($float == 'left') {
                    $floatClass = 'class="left-10"';
                } elseif ($float == 'right') {
                    $floatClass = 'class="right-10"';
                } else {
                    $floatClass = '';
                }

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" {$floatClass} height=\"200px\" alt=\"Peďák\"/></a>";

                $body = str_replace("(!photo_{$id}_{$float}!)", $tag, $body);

                $content->$parsedField = $body;
            }

            if ($type == 'gallery') {
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
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $config = Registry::get('configuration');

        $articlesPerPage = $config->news_per_page;

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/aktuality';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/aktuality/p/' . $page;
        }
        
        $content = $this->getCache()->get('news-' . $page);
        
        if ($content !== null) {
            $news = $content;
        } else {
            $news = App_Model_News::all(
                        array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s')), 
                array('id', 'urlKey', 'author', 'title', 'shortBody', 'created', 'rank'), 
                array('rank' => 'asc', 'created' => 'DESC'), (int)$articlesPerPage, (int) $page);

        if ($news !== null) {
            foreach ($news as $_news) {
                $this->_parseNewsBody($_news, 'shortBody');
            }
        } else {
            $news = array();
        }

            $this->getCache()->set('news-' . $page, $news);
        }

        $newsCount = App_Model_News::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );
        $newsPageCount = ceil($newsCount / $articlesPerPage);
        $view->set('newsbatch', $news)
                ->set('newspagecount', $newsPageCount);

        if ($newsPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $newsPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/aktuality/p/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/aktuality/p/' . $nextPage);
        }

        $layoutView->set('metatitle', 'ZKO - Aktuality')
                ->set('canonical', $canonical);
    }

    /**
     *
     * @param type $urlkey
     */
    public function detail($urlkey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $news = App_Model_News::first(
                        array(
                            'urlKey = ?' => $urlkey,
                            'active = ?' => true
        ));

        $newsParsed = $this->_parseNewsBody($news, 'body');

        $this->_checkMetaData($layoutView, $news);
        $layoutView
                ->set('article', 1)
                ->set('artcreated', $news->getCreated())
                ->set('artmodified', $news->getModified());

        $view->set('news', $newsParsed);
    }

}
