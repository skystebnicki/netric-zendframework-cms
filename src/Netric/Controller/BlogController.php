<?php
/**
 * Controller that handles basic blog functionality
 */

namespace Netric\Controller;

use Zend;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BlogController extends AbstractActionController
{
    /**
     * Show article index
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('Config');
        $netricApi = $this->getServiceLocator()->get('NetricApi');
        $catId = $this->getEvent()->getRouteMatch()->getParam("cat");
        $groupings = $netricApi->getEntityGroupings(
            "content_feed_post",
            "categories",
            array("feed_id"=>$config['netric']['blog_feed_id'])
        );

        if ($catId) {
            foreach ($$groupings as $group) {
                if ($group->id == $catId) {
                 $cat = $group;
                }
            }
        }

        // Get blog feed
        // ----------------------------------------------------
        $feed = $netricApi->getEntity("content_feed", $config['netric']['blog_feed_id']);
        
        // Get posts
        // ----------------------------------------------------
        $postCollection = $netricApi->createEntityCollection("content_feed_post");
        $postCollection->where("feed_id")->equals($config['netric']['blog_feed_id']);
        $postCollection->where("f_publish")->equals(true);
        if ($catId)
            $postCollection->where("categories")->equals($catId);
        $postCollection->orderBy("time_entered", "DESC");
        $postCollection->setLimit(20);
        $num = $postCollection->load();
        $posts = array();
        for ($i = 0; $i < $num; $i++)
        {
            $posts[] = $postCollection->getEntity($i);
        }

        // Setup view
        // ----------------------------------------------------
        $view = new ViewModel();
        $view->setTemplate("blog/index"); // Use $config / view_manager/template_map to override in global config
        $view->title = ($cat) ? $cat->title : $feed->getValue("title");
        $view->posts = $posts;
        $view->netricServer = $config['netric']['server'];
        
        // Get the categories subview
        // ----------------------------------------------------
        $categoriesView = new ViewModel();
		$categoriesView->currentCategory = $catId;
        $categoriesView->setTemplate("blog/categories"); // Use $config / view_manager/template_map to override in global config
        $categoriesView->categories = $groupings;
        $view->addChild($categoriesView, 'categories');
        
        // Get the subscription subview if set
        // ----------------------------------------------------
        $subsView = new ViewModel();
        $subsView->setTemplate("blog/subscribe"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($subsView, 'subscribe');
        
        // Get the share view
        // ----------------------------------------------------
        $shareView = new ViewModel();
        $shareView->setTemplate("sharethis"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($shareView, 'share');
        
        // Get the comments view
        // ----------------------------------------------------
        $commentsView = new ViewModel();
        $commentsView->setTemplate("comments"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($commentsView, 'comments');
        
        return $view;
    }

	/**
	 * Post view action
	 */
    public function postAction()
    {
		$uname = $this->getEvent()->getRouteMatch()->getParam("uname");
        $netricApi = $this->getServiceLocator()->get('NetricApi');
        $config = $this->getServiceLocator()->get('Config');
        
        $post = $netricApi->getEntityByUniqueName("content_feed_post", $uname);

        $view = new ViewModel();
        $view->setTemplate("blog/post"); // Use $config / view_manager/template_map to override in global config
        $view->title = $post->getValue("title");
		$view->post = $post;	
		$view->netricServer = $config['netric']['server'];
        $view->author = $post->getValue("author");
        $view->dateAndTime = ($post->getValue("time_entered")) ? $post->getValue("time_entered") : $post->getValue("ts_updated") ;
        
        // Get about information for this blog
        // ----------------------------------------------------
        $subsView = new ViewModel();
        $subsView->setTemplate("blog/about"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($subsView, 'about');

        // Get the subscription subview if set
        // ----------------------------------------------------
        $subsView = new ViewModel();
        $subsView->setTemplate("blog/subscribe"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($subsView, 'subscribe');
        
        // Get the share view
        // ----------------------------------------------------
        $shareView = new ViewModel();
        $shareView->setTemplate("sharethis"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($shareView, 'share');
        
        // Get the comments view
        // ----------------------------------------------------
        $commentsView = new ViewModel();
        $commentsView->setTemplate("comments"); // Use $config / view_manager/template_map to override in global config
        $view->addChild($commentsView, 'comments');
        
        // Set navigation to active
        // ----------------------------------------------------
        $nav = $this->getServiceLocator()->get('CmsNavigation');
        $navPage = $nav->findOneById("blog");
		if ($navPage)
        	$navPage->setActive();
        
        return $view;
    }

	/**
	 * Old Posts Redirect for legacy systems - zf1
	 */
    public function postsAction()
    {
		$uname = $this->getEvent()->getRouteMatch()->getParam("uname");
		return $this->redirect()->toUrl("/blog/" . $uname, array('code'=>301));
	}

	/**
	 * Old Posts Redirect for legacy systems - zf1
	 */
    public function allAction()
    {
		$uname = $this->getEvent()->getRouteMatch()->getParam("uname");
		return $this->redirect()->toUrl("/blog/" . $uname, array('code'=>301));
	}

	/**
	 * Render RSS feed
	 */
	public function feedAction()
	{
		$config = $this->getServiceLocator()->get('Config');
        $netricApi = $this->getServiceLocator()->get('NetricApi');
       
        
        // Get blog feed
        // ----------------------------------------------------
        $contentFeed = $netricApi->getEntity("content_feed", $config['netric']['blog_feed_id']);

		$feed = new Zend\Feed\Writer\Feed;
		$feed->setTitle($contentFeed->getValue("title"));
		$feed->setDescription(($contentFeed->getValue("description")) ? $contentFeed->getValue("description") : $contentFeed->getValue("title"));
		$feed->setLink('http://' . $_SERVER['SERVER_NAME']);
		$feed->setFeedLink('http://' . $_SERVER['SERVER_NAME'] . "/blog/feed/rss", 'rss');

        // Get posts
        // ----------------------------------------------------
        $postCollection = $netricApi->createEntityCollection("content_feed_post");
        $postCollection->where("feed_id")->equals($config['netric']['blog_feed_id']);
        $postCollection->andWhere("f_publish")->equals(true);
        if ($catId)
            $postCollection->where("categories")->equals($catId);
        $postCollection->orderBy("time_entered", "DESC");
        $postCollection->setLimit(500);
        $num = $postCollection->load();
        $posts = array();
        for ($i = 0; $i < $num; $i++)
        {
            $post = $postCollection->getEntity($i);

			/**
			 * Add one or more entries. Note that entries must
			 * be manually added once created.
			 */
			$entry = $feed->createEntry();
			$entry->setTitle($post->getValue('title'));
			$entry->setLink('http://www.example.com/all-your-base-are-belong-to-us');
			$entry->addAuthor(array(
				'name'  => 'Paddy',
				'email' => 'paddy@example.com',
				'uri'   => 'http://www.example.com',
			));
			$entry->setDateModified(time());
			$entry->setDateCreated(time());
			$entry->setDescription('Exposing the difficultly of porting games to English.');
			$entry->setContent("TEST"); // $post->getValue('data')
			$feed->addEntry($entry);
        }

		/**
		 * Render the resulting feed to Atom 1.0 and assign to $out.
		 * You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
		 */
		$out = $feed->export('rss');

        // Return the content
        $response = $this->getResponse();
        $response->getHeaders()->addHeaders(array('Content-type' => 'text/xml')); 
        $response->setContent($out); 
        return $response;
	}
}
