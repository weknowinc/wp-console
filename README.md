WP Console
=============================================
The WordPress CLI. A tool to generate boilerplate code, interact with and debug WordPress.

## Releases Page
All notable changes to this project will be documented in the [releases page](https://github.com/weknowinc/wp-console/releases)


# Install

These instructions are intented for Unix, Linux, Mac OSX system, use sudo if you get permissions errors

```
curl https://weknowinc.com/wp-console/installer -L -o wordpress.phar
mv wordpress.phar /usr/local/bin/wordpress
chmod +x /usr/local/bin/wordpress
```

## Usage

### Available commands:
  
  * about                             Display basic information about Wordpres Console project
  * chain                             Chain command execution
  * exec                              Execute an external command.
  * help                              Displays help for a command
  * init                              Copy configuration files.
  * list                              Lists all available commands

 **cache**
  * cache:flush (cf)                  Flush the Wordpress object cache

 **container**
   * container:debug (cod)            Displays current services for an application..

 **create**
  * create:users (cu)                 Create dummy users for your WordPress application.
  * create:roles (crr)                Create dummy roles for your Wordpress application.

 **debug**
  * debug:chain (dc)                  List available chain files.
  * debug:container (dco)             Displays current services for an application.
  * debug:multisite (dm)              List all sites in network available to a specific user
  * debug:plugin (dp)                 Display current plugins available for application
  * debug:roles (dusr)                Displays current roles for the application
  * debug:shortcode (ds)              Displays current shortcodes in your WordPress application.
  * debug:theme (dt)                  Display current themes available for application

 **generate**
  * generate:command (gc)                  Generate commands for the console.
  * generate:menu (gm)                     Generate a menu.
  * generate:metabox (gm)                  Generate a meta box.
  * generate:plugin (gp)                   Generate a plugin.
  * generate:post:type (gpt)               Generate a custom post type.
  * generate:quicktag (gqt)                Generate a quicktag.
  * generate:register:style (grs)          Generate a register style.
  * generate:shortcode (gs)                Generate a shortcode.  
  * generate:sidebar (gsb)                 Generate a sidebar.
  * generate:taxonomy (gta)                Generate a custom taxonomy.
  * generate:theme (gth)                   Generate a theme.
  * generate:toolbar (gtb)                 Generate a toolbar.
  * generate:user:contactmethods (gucm)    Generate a User contact methods.
  * generate:widget (gwd)                  Generate a widgets.

 
 **multisite**
  * multisite:install (mi)            Install a Wordpress multisite network
  * multisite:new (mn)                Add New Site a Wordpress multisite network
 
 **plugin**
  * plugin:activate (pa)              Activate plugins or plugin in the application
  * plugin:deactivate (pd)            Deactivate plugins or plugin in the application

 **site**
  * site:install (si)                 Install a Wordpress project
 
 **role**
  * role:delete (rd)                  Delete roles for the application
  * role:new (rn)                     Create roles for the application
 
 **theme**
  * theme:activate (ta)               Activate theme in the application

# How to contribute

## Fork
Fork your own copy of the [WordPress Console](https://bitbucket.org/weknowinc/wp-console/fork) repository to your account

## Clone
Get a copy of your recently cloned version of console in your machine.
```
$ git clone git@github.com:[your-git-user-here]/wp-console.git
```

## Configure a remote fork
```
$ git remote add upstream git@github.com:weknowinc/wp-console.git
```

## Sync your fork
```
$ git fetch upstream
$ git merge upstream/master
```

## Install composer dependencies

```
$ composer install.
```

# Supporting Organizations

[![weKnow](https://www.drupal.org/files/weKnow-logo_5.png)](http://weknowinc.com)

[![Anexus](https://www.drupal.org/files/anexus-logo.png)](http://www.anexusit.com/)

> WordPress is a registered trademark of [WordPress Foundation](http://wordpressfoundation.org/2010/trademark/).
