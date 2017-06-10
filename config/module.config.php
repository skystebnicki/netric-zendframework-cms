<?php
/**
 * Netric CMS (http://www.netric.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2012-2017 Aereus
 */

use NetricZend\Controller\PageController;
use NetricZend\Controller\SearchController;
use NetricZend\Controller\BlogController;
use NetricZend\Controller\ApiController;
use NetricSDK\NetricApi;
use NetricZend\Navigation\CmsNavigationFactory;
use NetricZend\Search\SearcherFactory;

use Zend\Mvc\Router\Http\Literal;
use Zend\I18n\Translator\TranslatorServiceFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return array(
    'router' => array(
        'routes' => array(
			// CMS Catchall route
			'cms' => array(
				'type' => 'regex',
				'options' => array(
					//'regex' => '/(?<page>.*)',
					'regex' => '/(?P<page>.*)',
					'defaults' => array(
						'controller' => PageController::class,
						'action'     => 'index',
					),
					'spec' => '%page%',
				),
				'priority' => -1000,
			),
            
            // Catch all for netric api
            'netric' => array(
                'type' => Literal::class,
                'options' => array(
                    'route'    => '/netric',
                    'defaults' => array(
                        '__NAMESPACE__' => 'NetricZend\Controller',
                        'controller' => 'Api',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'controller' => 'Netric\Controller\Api',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                ),
            ),

			// Search
            'search' => array(
                'type' => Literal::class,
                'options' => array(
                    'route'    => '/search',
                    'defaults' => array(
						'controller' => SearchController::class,
						'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),

			// Legacy api Router
            'antapi' => array(
                'type' => Literal::class,
                'options' => array(
                    'route'    => '/antapi',
                    'defaults' => array(
                        '__NAMESPACE__' => 'NetricZend\Controller',
                        'controller' => 'Api',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:action]',
                            'constraints' => array(
                                'controller' => 'Api',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'controller' => 'Netric\Controller\Api',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                ),
            ),


            'blog' => array(
                'type' => Literal::class,
                'options' => array(
                    'route'    => '/blog',
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
            
            // The following route is used to map blog categories
			'blogCategories' => array(
				'type'	=> 'Segment',
				"options" => array(
					"route"	=>  "/blog/category/[:cat]",
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'index',
                    ),
				),
			),

            // Blog Feed
			'blogFeeds' => array(
				'type'	=> 'Segment',
				"options" => array(
					"route"	=>  "/blog/feed/[:format]",
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'feed',
                    ),
				),
			),

			// The following is used for legacy links to /posts/[uname]
			'blogPostsLegacy' => array(
				'type'	=> 'Segment',
				"options" => array(
					"route"	=>  "/blog/posts/[:uname]",
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'posts',
                    ),
				),
			),

			// The following is used for legacy links to /All/[uname]
			'blogAllLegacy' => array(
				'type'	=> 'Segment',
				"options" => array(
					"route"	=>  "/blog/All/[:uname]",
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'all',
                    ),
				),
			),

			// The following route is used to map blog posts
			'blogPost' => array(
				'type'	=> 'Segment',
				"options" => array(
					"route"	=>  "/blog/[:uname]",
                    'defaults' => array(
                        'controller' => BlogController::class,
                        'action'     => 'post',
                    ),
				),
			),
        ),
    ),

    // Setup netric configs
    'netric' => array(
        'server' => 'https://aereus.netric.com',
        'applicationId' => '',
        'applicationKey' => '',
        'usehttps' => false,
        'site_id' => 1,
		'blog_feed_id' => 1,
		'search' => array(
			"enabled" => true,
			"entities" => array(
				array(
					"obj_type" => "content_feed_post",
					"label" => "Blog Post",
					"url_base" => "/blog",
					"conditions" => array(
						"feed_id" => 1, // change for site
					),
					/*
					"fields" => array(
						"title",
						"data",
					)
					 */
				),
				array(
					"obj_type" => "cms_page",
					"label" => "Page",
					"url_base" => "/",
					"conditions" => array(
						"f_publish" => true,
        				'site_id' => 1,
					),
					/*
					"fields" => array(
						"name",
						"data"
					)
					 */
				),
			),
		),
    ),
    'service_manager' => array(
        'factories' => array(
            'translator' => TranslatorServiceFactory::class,

            'CmsNavigation' => CmsNavigationFactory::class,

            'NetricApi' => function($sm) {
                $config = $sm->get('Config');
                return new NetricApi(
                    $config["netric"]["server"],
                    $config["netric"]["applicationId"],
                    $config["netric"]["applicationKey"]
                );
            },

            // Return a reference to an identitymapper for loading entities
            'NetricSearcher' => SearcherFactory::class,
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'NetricZend\Controller\BlogController' => BlogController::class,
            'NetricZend\Controller\PageController' => PageController::class,
            'NetricZend\Controller\ApiController' => ApiController::class,
            'NetricZend\Controller\SearchController' => SearchController::class,
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            //'layout/layout'          => __DIR__ . '/../view/layout/layout.phtml',
            'blog/index'               => __DIR__ . '/../view/netric/blog/index.phtml',
            'blog/post'                => __DIR__ . '/../view/netric/blog/post.phtml',
            'blog/categories'          => __DIR__ . '/../view/netric/snippets/blog_categories.phtml',
            'blog/subscribe'           => __DIR__ . '/../view/netric/snippets/blog_subscribe.phtml',
            'blog/about'               => __DIR__ . '/../view/netric/snippets/blog_about.phtml',
            'sharethis'                => __DIR__ . '/../view/netric/snippets/share.phtml',
            'comments'                 => __DIR__ . '/../view/netric/snippets/comments.phtml',
            'breadcrumb'               => __DIR__ . '/../view/netric/snippets/breadcrumb.phtml',
            'cms/page'                 => __DIR__ . '/../view/netric/page/index.phtml',
            //'error/404'              => __DIR__ . '/../view/error/404.phtml',
            //'error/index'            => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
