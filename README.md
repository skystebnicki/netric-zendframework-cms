# Netric CMS ZF2 Module

## Summary

This is a Zend Framework Module that enables you to use a netric account as the CMS
for any website.

## Installation

In your composer.json add the following:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/skystebnicki/netric-zendframework-cms"
        }
    ],

Then in the dependencies add:

    "require": {
        "NetricZend": "master@dev",
    }

Now in /config/autoload/global.php add the following settings:

    // API settings
    'netric' => array(
        'server' => 'https://myaccount.netric.com',
        'applicationId' => 'YOUR_API_APPLICATION_ID',
        'applicationKey' => 'YOUR_API_APPLICATION_KEY',
        'usehttps' => true,
        'site_id' => 'ID FROM CMS/Sites',
        'blog_feed_id' => 'ID FROM CMS/Feeds',
    ),

## Usage

By default, any unmatced routes in your application config will be handed by the CMS
moddle by attempting to load a page for the site_id indcated in your config with a
matching uname to the URL.
