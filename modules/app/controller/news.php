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
            $layoutView->set('metaogimage', "http://{$this->getServerHost()}{$object->getMetaImage()}");
        }

        $layoutView->set('canonical', "http://{$this->getServerHost()}/aktuality/r/" . $object->getUrlKey() . '/')
                ->set('metaogurl', "http://{$this->getServerHost()}/aktuality/r/" . $object->getUrlKey() . '/')
                ->set('metaogtype', 'article');

        return;
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
        
        if(null === $news){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/aktuality');
        }

        $this->_checkMetaData($layoutView, $news);
        $layoutView
                ->set('article', 1)
                ->set('artcreated', $news->getCreated())
                ->set('artmodified', $news->getModified());

        $view->set('news', $news);
    }

}
