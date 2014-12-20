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

            $this->getCache()->set('news-1', $news);
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

        $canonical = 'http://' . $this->getServerHost() . '/historie';

        $view->set('content', $content);

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

        $canonical = 'http://' . $this->getServerHost() . '/akce';

        $view->set('content', $content);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'ZKO - Akce');
    }

}
