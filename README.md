Requirements
============

* Wordpress >= 3.3r18756
* Symfony 2.0.x

Usage 
=====

Imagine you are in a Controller:

    class DemoController extends Controller
    {
        /**
         * @Route("/hello/{name}", name="_demo_hello")
         * @Template()
         */
        public function helloAction($name)
        {
            // retrieve currently logged-in user using the Wordpress API
            $user = $this->get('wordpress.api.abstraction')->wp_get_current_user();
            
            // retrieve user #2
            $user = new \WP_User(2);

            return array('username' => $user->user_login);
        }

        // ...
    }

Installation
============

1. Make sure Wordpress's cookies are accessible from your Symfony 2 application. To confirm this, 
   open up Symfony's profiler and look for `wordpress_test_cookie` inside the request tab.  
   If you can't find the test cookie in request tab, please try to redefine the cookie path or 
   domain used by Wordpress by editing `wp-config.php`.  
   For more information, please [read the Wordpress Codex](http://codex.wordpress.org/Editing_wp-config.php)

        // wordpress/wp-config.php

        define('COOKIEPATH', '/' );
        define('COOKIE_DOMAIN', '.yourdomain.com');

2. Register the namespace `Hypebeast` to your project's autoloader bootstrap script:

        // app/autoload.php

        $loader->registerNamespaces(array(
              // ...
              'Hypebeast'    => __DIR__.'/../vendor/bundles',
              // ...
        ));

3. Add this bundle to your application's kernel:

        // app/AppKernel.php

        public function registerBundles()
        {
            return array(
                // ...
                new Hypebeast\WordpressBundle\HypebeastWordpressBundle(),
                // ...
            );
        }

4. Configure the Wordpress service in your YAML configuration.
        
        # app/config/config.yml
        
        hypebeast_wordpress:
            wordpress_path: /path/to/your/wordpress

5. Add Wordpress factory and firewall to your `security.yml`. Wordpress authentication is a stateless authentication, no login cookie should be ever created by Symfony2. Below is a sample configuration. 
All of the options for the wordpress_* authentication methods are optional and are displayed with 
their default values. You can omit them if you use the defaults, e.g. `wordpress_cookie: ~` and 
`wordpress_form_login: ~`

        # app/config/security.yml
        
        security:
            
            # ...
            
            factories:
                - "%kernel.root_dir%/../vendor/bundles/Hypebeast/WordpressBundle/Resources/config/security_factories.xml"
            
            firewalls:
                secured_area:
                    pattern:    ^/demo/secured/
                    stateless:  true
                    wordpress_cookie:
                        # Set to false if you want to use a login form within your Symfony app to 
                        # collect the user's Wordpress credentials (see below) or any other
                        # authentication provider. Otherwise, the user will be redirected to your 
                        # Wordpress login if they need to authenticate
                        redirect_to_wordpress_on_failure: true

                    # Because this is based on form_login, it accepts all its parameters as well
                    # See the http://symfony.com/doc/2.0/cookbook/security/form_login.html for more 
                    # details. Omit this if using Wordpress's built-in login, as above
                    wordpress_form_login:
                        # This is the name of the POST parameter that can be used to indicate 
                        # whether the user should be remembered via Wordpress's remember-me cookie
                        remember_me_parameter: _remember_me

                    # anonymous:  ~
                    
                # ...

Caveats
=======

* Wordpress assumes it will be run in the global scope, so some of its code doesn't even bother 
  explicitly globalising variables. The required version of Wordpress core marginally improves this 
  situation (enough to allow us to integrate with it), but beware that other parts of Wordpress or 
  plugins may still have related issues.
* There is currently no user provider (use the API abstraction, see example above)
* Authentication errors from Wordpress are passed through unchanged and, since Wordpress uses HTML 
  in its errors, the user may see HTML tags