<?php
/*
Plugin Name: cleancoded Varnish
Plugin URI: http://github.com/cleancoded/varnish-wp-cache
Version: 1.0
Author: cleancoded
Description: A plugin for purging Varnish cache when content is published or edited.
*/

class cleancodedVarnish {
  public $cleancoded_addr_optname;
  public $cleancoded_port_optname;
  public $cleancoded_secret_optname;
  public $cleancoded_timeout_optname;
  public $cleancoded_update_pagenavi_optname;
  public $cleancoded_update_commentnavi_optname;
  public $cleancoded_purgeactions;

  function cleancodedVarnish() {
    global $post;

    $this->cleancoded_addr_optname = "cleancodedVarnish_addr";
    $this->cleancoded_port_optname = "cleancodedVarnish_port";
    $this->cleancoded_secret_optname = "cleancodedVarnish_secret";
    $this->cleancoded_timeout_optname = "cleancodedVarnish_timeout";
    $this->cleancodedVarnish_purge_url_optname = "cleancodedVarnish_purge_url";
    $this->cleancoded_update_pagenavi_optname = "cleancodedVarnish_update_pagenavi";
    $this->cleancoded_update_commentnavi_optname = "cleancodedVarnish_update_commentnavi";
    $this->cleancoded_use_adminport_optname = "cleancodedVarnish_use_adminport";
    $this->cleancoded_vversion_optname = "cleancodedVarnish_vversion";
    $this->cleancoded_purgeactions = array();
    $cleancoded_addr_optval = array ("127.0.0.1");
    $cleancoded_port_optval = array (80);
    $cleancoded_secret_optval = array ("");
    $cleancoded_timeout_optval = 5;
    $cleancoded_update_pagenavi_optval = 0;
    $cleancoded_update_commentnavi_optval = 0;
    $cleancoded_use_adminport_optval = 0;
    $cleancoded_vversion_optval = 2;

    if ( (get_option($this->cleancoded_addr_optname) == FALSE) ) {
      add_option($this->cleancoded_addr_optname, $cleancoded_addr_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_port_optname) == FALSE) ) {
      add_option($this->cleancoded_port_optname, $cleancoded_port_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_secret_optname) == FALSE) ) {
      add_option($this->cleancoded_secret_optname, $cleancoded_secret_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_timeout_optname) == FALSE) ) {
      add_option($this->cleancoded_timeout_optname, $cleancoded_timeout_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_update_pagenavi_optname) == FALSE) ) {
      add_option($this->cleancoded_update_pagenavi_optname, $cleancoded_update_pagenavi_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_update_commentnavi_optname) == FALSE) ) {
      add_option($this->cleancoded_update_commentnavi_optname, $cleancoded_update_commentnavi_optval, '', 'yes');
    }

    if ( (get_option($this->cleancoded_use_adminport_optname) == FALSE) ) {
      add_option($this->cleancoded_use_adminport_optname, $cleancoded_use_adminport_optval, '', 'yes');
    }

    if ( 
        (get_option($this->cleancoded_vversion_optname) == FALSE) ) {
      add_option($this->cleancoded_vversion_optname, $cleancoded_vversion_optval, '', 'yes');
    }

    // Localization init
    add_action('init', array($this, 'cleancodedVarnishLocalization'));

    // Add Administration Interface
    add_action('admin_menu', array($this, 'cleancodedVarnishAdminMenu'));

    // Add Purge Links to Admin Bar
    add_action('admin_bar_menu', array($this, 'cleancodedVarnishAdminBarLinks'), 100);

    // When posts/pages are published, edited or deleted
    // 'edit_post' is not used as it is also executed when a comment is changed,
    // causing the plugin to purge several URLs (cleancodedVarnishPurgeCommonObjects)
    // that do not need purging.
    
    // When a post or custom post type is published, or if it is edited and its status is "published".
    add_action('publish_post', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('publish_post', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // When a page is published, or if it is edited and its status is "published".
    add_action('publish_page', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('publish_page', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // When an attachment is updated.
    add_action('edit_attachment', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('edit_attachment', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // Runs just after a post is added via email.
    add_action('publish_phone', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('publish_phone', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // Runs when a post is published via XMLRPC request, or if it is edited via XMLRPC and its status is "published".
    add_action('xmlrpc_publish_post', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('xmlrpc_publish_post', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // Runs when a future post or page is published.
    add_action('publish_future_post', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('publish_future_post', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);
    // When post status is changed
    add_action('transition_post_status', array($this, 'cleancodedVarnishPurgePostStatus'), 99, 3);
    add_action('transition_post_status', array($this, 'cleancodedVarnishPurgeCommonObjectsStatus'), 99, 3);
    // When posts, pages, attachments are deleted
    add_action('deleted_post', array($this, 'cleancodedVarnishPurgePost'), 99);
    add_action('deleted_post', array($this, 'cleancodedVarnishPurgeCommonObjects'), 99);

    // When comments are made, edited or deleted
    // See: http://codex.wordpress.org/Plugin_API/Action_Reference#Comment.2C_Ping.2C_and_Trackback_Actions
    add_action('comment_post', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('edit_comment', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('deleted_comment', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('trashed_comment', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('pingback_post', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('trackback_post', array($this, 'cleancodedVarnishPurgePostComments'),99);
    add_action('wp_set_comment_status', array($this, 'cleancodedVarnishPurgePostCommentsStatus'),99);

    // When Theme is changed, Thanks dupuis
    add_action('switch_theme',array($this, 'cleancodedVarnishPurgeAll'), 99);

    // When a new plugin is loaded
    // this was added due to Issue #12, but, doesn't do what was intended
    // commenting this out gets rid of the incessant purging.
    //add_action('plugins_loaded',array($this, 'cleancodedVarnishPurgeAll'), 99);

    // Do the actual purges only on shutdown to ensure a single URL is only purged once. IOK 2016-01-20
    add_action('shutdown',array($this,'cleancodedVarnishPurgeOnExit'),99);
  }

  function cleancodedVarnishLocalization() {
    load_plugin_textdomain('cleancoded-varnish', false, dirname(plugin_basename( __FILE__ ) ) . '/lang/');
  }

    // cleancodedVarnishPurgeAll - Using a regex, clear all blog cache. Use carefully.
    function cleancodedVarnishPurgeAll() {
        $this->cleancodedVarnishPurgeObject('/.*');
    }

    // cleancodedVarnishPurgeURL - Using a URL, clear the cache
    function cleancodedVarnishPurgeURL($cleancoded_purl) {
        $cleancoded_purl = preg_replace( '#^https?://[^/]+#i', '', $cleancoded_purl );
        $this->cleancodedVarnishPurgeObject($cleancoded_purl);
    }

    //wrapper on cleancodedVarnishPurgeCommonObjects for transition_post_status
    function cleancodedVarnishPurgeCommonObjectsStatus($old, $new, $post) {
        if ( $old != $new ) {
            if ( $old == 'publish' || $new == 'publish' ) {
                $this->cleancodedVarnishPurgeCommonObjects($post->ID);
            }
        }
    }

    // Purge related objects
    function cleancodedVarnishPurgeCommonObjects($post_id) {

        $post = get_post($post_id);
        // We need a post object in order to generate the archive URLs which are
        // related to the post. We perform a few checks to make sure we have a
        // post object.
        if ( ! is_object($post) || ! isset($post->post_type) || ! in_array( get_post_type($post), array('post') ) ) {
            // Do nothing for pages, attachments.
            return;
        }
        
        // NOTE: Policy for archive purging
        // By default, only the first page of the archives is purged. If
        // 'cleancoded_update_pagenavi_optname' is checked, then all the pages of each
        // archive are purged.
        if ( get_option($this->cleancoded_update_pagenavi_optname) == 1 ) {
            // Purge all pages of the archive.
            $archive_pattern = '(?:page/[\d]+/)?$';
        } else {
            // Only first page of the archive is purged.
            $archive_pattern = '$';
        }

        // Front page (latest posts OR static front page)
        $this->cleancodedVarnishPurgeObject( '/' . $archive_pattern );

        // Static Posts page (Added only if a static page used as the 'posts page')
        if ( get_option('show_on_front', 'posts') == 'page' && intval(get_option('page_for_posts', 0)) > 0 ) {
            $posts_page_url = preg_replace( '#^https?://[^/]+#i', '', get_permalink(intval(get_option('page_for_posts'))) );
            $this->cleancodedVarnishPurgeObject( $posts_page_url . $archive_pattern );
        }

        // Feeds
        $this->cleancodedVarnishPurgeObject( '/feed/(?:(atom|rdf)/)?$' );

        // Category, Tag, Author and Date Archives

        // We get the URLs of the category and tag archives, only for
        // those categories and tags which have been attached to the post.

        // Category Archive
        $category_slugs = array();
        foreach( get_the_category($post->ID) as $cat ) {
            $category_slugs[] = $cat->slug;
        }
        if ( ! empty($category_slugs) ) {
            if ( count($category_slugs) > 1 ) {
                $cat_slug_pattern = '(' . implode('|', $category_slugs) . ')';
            } else {
                $cat_slug_pattern = implode('', $category_slugs);
            }
            $this->cleancodedVarnishPurgeObject( '/' . get_option('category_base', 'category') . '/' . $cat_slug_pattern . '/' . $archive_pattern );
        }

        // Tag Archive
        $tag_slugs = array();
        foreach( get_the_tags($post->ID) as $tag ) {
            $tag_slugs[] = $tag->slug;
        }
        if ( ! empty($tag_slugs) ) {
            if ( count($tag_slugs) > 1 ) {
                $tag_slug_pattern = '(' . implode('|', $tag_slugs) . ')';
            } else {
                $tag_slug_pattern = implode('', $tag_slugs);
            }
            $this->cleancodedVarnishPurgeObject( '/' . get_option('tag_base', 'tag') . '/' . $tag_slug_pattern . '/' . $archive_pattern );
        }

        // Author Archive
        $author_archive_url = preg_replace('#^https?://[^/]+#i', '', get_author_posts_url($post->post_author) );
        $this->cleancodedVarnishPurgeObject( $author_archive_url . $archive_pattern );

        // Date based archives
        $archive_year = mysql2date('Y', $post->post_date);
        $archive_month = mysql2date('m', $post->post_date);
        $archive_day = mysql2date('d', $post->post_date);
        // Yearly Archive
        $archive_year_url = preg_replace('#^https?://[^/]+#i', '', get_year_link( $archive_year ) );
        $this->cleancodedVarnishPurgeObject( $archive_year_url . $archive_pattern );
        // Monthly Archive
        $archive_month_url = preg_replace('#^https?://[^/]+#i', '', get_month_link( $archive_year, $archive_month ) );
        $this->cleancodedVarnishPurgeObject( $archive_month_url . $archive_pattern );
        // Daily Archive
        $archive_day_url = preg_replace('#^https?://[^/]+#i', '', get_day_link( $archive_year, $archive_month, $archive_day ) );
        $this->cleancodedVarnishPurgeObject( $archive_day_url . $archive_pattern );
    }

    //wrapper on cleancodedVarnishPurgePost for transition_post_status
    function cleancodedVarnishPurgePostStatus($old, $new, $post) {
        if ( $old != $new ) {
            if ( $old == 'publish' || $new == 'publish' ) {
                $this->cleancodedVarnishPurgePost($post->ID);
            }
        }
    }

    // cleancodedVarnishPurgePost - Purges a post object
    function cleancodedVarnishPurgePost($post_id, $purge_comments=false) {

        $post = get_post($post_id);
        // We need a post object, so we perform a few checks.
        if ( ! is_object($post) || ! isset($post->post_type) || ! in_array( get_post_type($post), array('post', 'page', 'attachment') ) ) {
            return;
        }

        //$cleancoded_url = get_permalink($post->ID);
        // Here we do not use ``get_permalink()`` to get the post object's permalink,
        // because this function generates a permalink only for published posts.
        // So, for example, there is a problem when a post transitions from
        // status 'publish' to status 'draft', because ``get_permalink`` would
        // return a URL of the form, ``?p=123``, which does not exist in the cache.
        // For this reason, the following workaround is used:
        //   http://wordpress.stackexchange.com/a/42988/14743
        // It creates a clone of the post object and pretends it's published and
        // then it generates the permalink for it.
        if (in_array($post->post_status, array('draft', 'pending', 'auto-draft'))) {
            $my_post = clone $post;
            $my_post->post_status = 'published';
            $my_post->post_name = sanitize_title($my_post->post_name ? $my_post->post_name : $my_post->post_title, $my_post->ID);
            $cleancoded_url = get_permalink($my_post);
        } else {
            $cleancoded_url = get_permalink($post->ID);
        }

        $cleancoded_url = preg_replace( '#^https?://[^/]+#i', '', $cleancoded_url );

        // Purge post comments feed and comment pages, if requested, before
        // adding multipage support.
        if ( $purge_comments === true ) {
            // Post comments feed
            $this->cleancodedVarnishPurgeObject( $cleancoded_url . 'feed/(?:(atom|rdf)/)?$' );
            // For paged comments
            if ( intval(get_option('page_comments', 0)) == 1 ) {
                if ( get_option($this->cleancoded_update_commentnavi_optname) == 1 ) {
                    $this->cleancodedVarnishPurgeObject( $cleancoded_url . 'comment-page-[\d]+/(?:#comments)?$' );
                }
            }
        }

        // Add support for multipage content for posts and pages
        if ( in_array( get_post_type($post), array('post', 'page') ) ) {
            $cleancoded_url .= '([\d]+/)?$';
        }
        // Purge object permalink
        $this->cleancodedVarnishPurgeObject($cleancoded_url);

        // For attachments, also purge the parent post, if it is published.
        if ( get_post_type($post) == 'attachment' ) {
            if ( $post->post_parent > 0 ) {
                $parent_post = get_post( $post->post_parent );
                if ( $parent_post->post_status == 'publish' ) {
                    // If the parent post is published, then purge its permalink
                    $cleancoded_url = preg_replace( '#^https?://[^/]+#i', '', get_permalink($parent_post->ID) );
                    $this->cleancodedVarnishPurgeObject( $cleancoded_url );
                }
            }
        }
    }

    // wrapper on cleancodedVarnishPurgePostComments for comment status changes
    function cleancodedVarnishPurgePostCommentsStatus($comment_id, $new_comment_status) {
        $this->cleancodedVarnishPurgePostComments($comment_id);
    }

    // cleancodedVarnishPurgePostComments - Purge all comments pages from a post
    function cleancodedVarnishPurgePostComments($comment_id) {
        $comment = get_comment($comment_id);
        $post = get_post( $comment->comment_post_ID );

        // Comments feed
        $this->cleancodedVarnishPurgeObject( '/comments/feed/(?:(atom|rdf)/)?$' );

        // Purge post page, post comments feed and post comments pages
        $this->cleancodedVarnishPurgePost($post, $purge_comments=true);

        // Popup comments
        // See:
        // - http://codex.wordpress.org/Function_Reference/comments_popup_link
        // - http://codex.wordpress.org/Template_Tags/comments_popup_script
        $this->cleancodedVarnishPurgeObject( '/.*comments_popup=' . $post->ID . '.*' );

    }

function cleancodedVarnishPostID() {
    global $posts, $comment_post_ID, $post_ID;

    if ($post_ID) {
        return $post_ID;
    } elseif ($comment_post_ID) {
        return $comment_post_ID;
    } elseif (is_single() || is_page() && count($posts)) {
        return $posts[0]->ID;
    } elseif (isset($_REQUEST['p'])) {
        return (integer) $_REQUEST['p'];
    }

    return 0;
}

  function cleancodedVarnishAdminMenu() {
    if (!defined('VARNISH_HIDE_ADMINMENU')) {
      add_options_page(__('cleancoded-varnish Configuration','cleancoded-varnish'), 'cleancoded-varnish', 'publish_posts', 'cleancodedVarnish', array($this, 'cleancodedVarnishAdmin'));
    }
  }

  function cleancodedVarnishAdminBarLinks($admin_bar){
    $admin_bar->add_menu( array(
      'id'    => 'cleancoded-varnish',
      'title' => __('Varnish','cleancoded-varnish'),
      'href' => admin_url('admin.php?page=cleancodedVarnish')
    ));
    $admin_bar->add_menu( array(
      'id'    => 'clear-all-cache',
      'parent' => 'cleancoded-varnish',
      'title' => 'Purge All Cache',
      'href'  => wp_nonce_url(admin_url('admin.php?page=cleancodedVarnish&amp;cleancodedVarnish_clear_blog_cache&amp;noheader=true'), 'cleancoded-varnish')
    ));
    $admin_bar->add_menu( array(
      'id'    => 'clear-single-cache',
      'parent' => 'cleancoded-varnish',
      'title' => 'Purge This Page',
      'href'  => wp_nonce_url(admin_url('admin.php?page=cleancodedVarnish&amp;cleancodedVarnish_clear_post&amp;noheader=true&amp;post_id=' . $this->cleancodedVarnishPostID() ), 'cleancoded-varnish')
    ));
  }



  // cleancodedVarnishAdmin - Draw the administration interface.
  function cleancodedVarnishAdmin() {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
       if (current_user_can('manage_options')) {

          $nonce = $_REQUEST['_wpnonce'];

          if (isset($_GET['cleancodedVarnish_clear_blog_cache']) && wp_verify_nonce( $nonce, 'cleancoded-varnish' )) {
            $this->cleancodedVarnishPurgeAll();
            header('Location: '.admin_url('admin.php?page=cleancodedVarnish'));
          }

          if (isset($_GET['cleancodedVarnish_clear_post']) && wp_verify_nonce( $nonce, 'cleancoded-varnish' )) {
            $this->cleancodedVarnishPurgePost($_GET['post_id']);
            header('Location: '.admin_url('admin.php?page=cleancodedVarnish'));
          }
       }
    }elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
       if (current_user_can('manage_options')) {
          if (isset($_POST['cleancodedVarnish_admin'])) {
             cleanSubmittedData('cleancodedVarnish_port', '/[^0-9]/');
             cleanSubmittedData('cleancodedVarnish_addr', '/[^0-9.]/');
             if (!empty($_POST["$this->cleancoded_addr_optname"])) {
                $cleancoded_addr_optval = $_POST["$this->cleancoded_addr_optname"];
                update_option($this->cleancoded_addr_optname, $cleancoded_addr_optval);
             }

             if (!empty($_POST["$this->cleancoded_port_optname"])) {
                $cleancoded_port_optval = $_POST["$this->cleancoded_port_optname"];
                update_option($this->cleancoded_port_optname, $cleancoded_port_optval);
             }

             if (!empty($_POST["$this->cleancoded_secret_optname"])) {
                $cleancoded_secret_optval = $_POST["$this->cleancoded_secret_optname"];
                update_option($this->cleancoded_secret_optname, $cleancoded_secret_optval);
             }

             if (!empty($_POST["$this->cleancoded_timeout_optname"])) {
                $cleancoded_timeout_optval = $_POST["$this->cleancoded_timeout_optname"];
                update_option($this->cleancoded_timeout_optname, $cleancoded_timeout_optval);
             }

             if (!empty($_POST["$this->cleancoded_update_pagenavi_optname"])) {
                update_option($this->cleancoded_update_pagenavi_optname, 1);
             } else {
                update_option($this->cleancoded_update_pagenavi_optname, 0);
             }

             if (!empty($_POST["$this->cleancoded_update_commentnavi_optname"])) {
                update_option($this->cleancoded_update_commentnavi_optname, 1);
             } else {
                update_option($this->cleancoded_update_commentnavi_optname, 0);
             }

             if (!empty($_POST["$this->cleancoded_use_adminport_optname"])) {
                update_option($this->cleancoded_use_adminport_optname, 1);
             } else {
                update_option($this->cleancoded_use_adminport_optname, 0);
             }

             if (!empty($_POST["$this->cleancoded_vversion_optname"])) {
                $cleancoded_vversion_optval = $_POST["$this->cleancoded_vversion_optname"];
                update_option($this->cleancoded_vversion_optname, $cleancoded_vversion_optval);
             }
          }

          if (isset($_POST['cleancodedVarnish_purge_url_submit'])) {
              $this->cleancodedVarnishPurgeURL($_POST["$this->cleancodedVarnish_purge_url_optname"]);
          }

          if (isset($_POST['cleancodedVarnish_clear_blog_cache']))
             $this->cleancodedVarnishPurgeAll();

          ?><div class="updated"><p><?php echo __('Settings Saved!','cleancoded-varnish' ); ?></p></div><?php
       } else {
          ?><div class="updated"><p><?php echo __('You do not have the privileges.','cleancoded-varnish' ); ?></p></div><?php
       }
    }

         $cleancoded_timeout_optval = get_option($this->cleancoded_timeout_optname);
         $cleancoded_update_pagenavi_optval = get_option($this->cleancoded_update_pagenavi_optname);
         $cleancoded_update_commentnavi_optval = get_option($this->cleancoded_update_commentnavi_optname);
         $cleancoded_use_adminport_optval = get_option($this->cleancoded_use_adminport_optname);
         $cleancoded_vversion_optval = get_option($this->cleancoded_vversion_optname);
    ?>
    <div class="wrap">
      <script type="text/javascript" src="<?php echo plugins_url('cleancoded-varnish.js', __FILE__ ); ?>"></script>
      <h2><?php echo __("cleancoded Varnish Administration",'cleancoded-varnish'); ?></h2>
      <h3><?php echo __("IP address and port configuration",'cleancoded-varnish'); ?></h3>
      <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <?php
          // Can't be edited - already defined in wp-config.php
          global $varnish_servers;
          global $varnish_version;
          if (is_array($varnish_servers)) {
             echo "<p>" . __("These values can't be edited since there's a global configuration located in <em>wp-config.php</em>. If you want to change these settings, please update the file or contact the administrator.",'cleancoded-varnish') . "</p>\n";
             // Also, if defined, show the varnish servers configured (VARNISH_SHOWCFG)
             if (defined('VARNISH_SHOWCFG')) {
                echo "<h3>" . __("Current configuration:",'cleancoded-varnish') . "</h3>\n";
                echo "<ul>";
                if ( isset($varnish_version) && $varnish_version )
                   echo "<li>" . __("Version: ",'cleancoded-varnish') . $varnish_version . "</li>";
                foreach ($varnish_servers as $server) {
                   @list ($host, $port, $secret) = explode(':', $server);
                   echo "<li>" . __("Server: ",'cleancoded-varnish') . $host . "<br/>" . __("Port: ",'cleancoded-varnish') . $port . "</li>";
                }
                echo "</ul>";
             }
          } else {
          // If not defined in wp-config.php, use individual configuration.
    ?>
       <!-- <table class="form-table" id="form-table" width=""> -->
       <table class="form-table" id="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __("Varnish Administration IP Address",'cleancoded-varnish'); ?></th>
            <th scope="row"><?php echo __("Varnish Administration Port",'cleancoded-varnish'); ?></th>
            <th scope="row"><?php echo __("Varnish Secret",'cleancoded-varnish'); ?></th>
        </tr>
        <script>
        <?php
          $addrs = get_option($this->cleancoded_addr_optname);
          $ports = get_option($this->cleancoded_port_optname);
          $secrets = get_option($this->cleancoded_secret_optname);
          //echo "rowCount = $i\n";
          for ($i = 0; $i < count ($addrs); $i++) {
             // let's center the row creation in one spot, in javascript
             echo "addRow('form-table', $i, '$addrs[$i]', $ports[$i], '$secrets[$i]');\n";
        } ?>
        </script>
	</table>

      <br/>

      <table>
        <tr>
          <td colspan="3"><input type="button" class="" name="cleancodedVarnish_admin" value="+" onclick="addRow ('form-table', rowCount)" /> <?php echo __("Add one more server",'cleancoded-varnish'); ?></td>
        </tr>
      </table>
      <?php
         }
      ?>
      <p><?php echo __("Timeout",'cleancoded-varnish'); ?>: <input class="small-text" type="text" name="cleancodedVarnish_timeout" value="<?php echo $cleancoded_timeout_optval; ?>" /> <?php echo __("seconds",'cleancoded-varnish'); ?></p>

      <p><input type="checkbox" name="cleancodedVarnish_use_adminport" value="1" <?php if ($cleancoded_use_adminport_optval == 1) echo 'checked '?>/> <?php echo __("Use admin port instead of PURGE method.",'cleancoded-varnish'); ?></p>

      <p><input type="checkbox" name="cleancodedVarnish_update_pagenavi" value="1" <?php if ($cleancoded_update_pagenavi_optval == 1) echo 'checked '?>/> <?php echo __("Also purge all page navigation (experimental, use carefully, it will include a bit more load on varnish servers.)",'cleancoded-varnish'); ?></p>

      <p><input type="checkbox" name="cleancodedVarnish_update_commentnavi" value="1" <?php if ($cleancoded_update_commentnavi_optval == 1) echo 'checked '?>/> <?php echo __("Also purge all comment navigation (experimental, use carefully, it will include a bit more load on varnish servers.)",'cleancoded-varnish'); ?></p>

      <p><?php echo __('Varnish Version', 'cleancoded-varnish'); ?>: <select name="cleancodedVarnish_vversion"><option value="2" <?php if ($cleancoded_vversion_optval == 2) echo 'selected '?>/> 2 </option><option value="3" <?php if ($cleancoded_vversion_optval == 3) echo 'selected '?>/> 3 </option></select></p>

      <p class="submit"><input type="submit" class="button-primary" name="cleancodedVarnish_admin" value="<?php echo __("Save Changes",'cleancoded-varnish'); ?>" /></p>

      <p>
        <?php echo __('Purge a URL', 'cleancoded-varnish'); ?>:<input class="text" type="text" name="cleancodedVarnish_purge_url" value="<?php echo get_bloginfo('url'), '/'; ?>" />
        <input type="submit" class="button-primary" name="cleancodedVarnish_purge_url_submit" value="<?php echo __("Purge",'cleancoded-varnish'); ?>" />
      </p>

      <p class="submit"><input type="submit" class="button-primary" name="cleancodedVarnish_clear_blog_cache" value="<?php echo __("Purge All Blog Cache",'cleancoded-varnish'); ?>" /> <?php echo __("Use only if necessary, and carefully as this will include a bit more load on varnish servers.",'cleancoded-varnish'); ?></p>
      </form>
    </div>
  <?php
  }

  // cleancodedVarnishPurgeObject - Takes a location as an argument and purges this object
  // from the varnish cache.
  // IOK 2015-12-21 changed to delay this to shutdown time.
   function cleancodedVarnishPurgeObject($cleancoded_url) {
    $this->cleancoded_purgeactions[$cleancoded_url]=$cleancoded_url;
  }
  function cleancodedVarnishPurgeOnExit() {
   $purgeurls = array_keys($this->cleancoded_purgeactions);
   foreach($purgeurls as $cleancoded_url) {
    $this->cleancodedVarnishActuallyPurgeObject($cleancoded_url);
   }
  }
  function cleancodedVarnishActuallyPurgeObject($cleancoded_url) {
    global $varnish_servers;

    // added this hook to enable other plugins do something when cache is purged
    do_action( 'cleancodedVarnishPurgeObject', $cleancoded_url );

    if (is_array($varnish_servers)) {
       foreach ($varnish_servers as $server) {
          list ($host, $port, $secret) = explode(':', $server);
          $cleancoded_purgeaddr[] = $host;
          $cleancoded_purgeport[] = $port;
          $cleancoded_secret[] = $secret;
       }
    } else {
       $cleancoded_purgeaddr = get_option($this->cleancoded_addr_optname);
       $cleancoded_purgeport = get_option($this->cleancoded_port_optname);
       $cleancoded_secret = get_option($this->cleancoded_secret_optname);
    }

    $cleancoded_timeout = get_option($this->cleancoded_timeout_optname);
    $cleancoded_use_adminport = get_option($this->cleancoded_use_adminport_optname);
    global $varnish_version;
    if ( isset($varnish_version) && in_array($varnish_version, array(2,3)) )
       $cleancoded_vversion_optval = $varnish_version;
    else
       $cleancoded_vversion_optval = get_option($this->cleancoded_vversion_optname);

    // check for domain mapping plugin by donncha
    if (function_exists('domain_mapping_siteurl')) {
        $cleancoded_wpurl = domain_mapping_siteurl('NA');
    } else {
        $cleancoded_wpurl = get_bloginfo('url');
    }
    $cleancoded_replace_wpurl = '/^https?:\/\/([^\/]+)(.*)/i';
    $cleancoded_host = preg_replace($cleancoded_replace_wpurl, "$1", $cleancoded_wpurl);
    $cleancoded_blogaddr = preg_replace($cleancoded_replace_wpurl, "$2", $cleancoded_wpurl);
    $cleancoded_url = $cleancoded_blogaddr . $cleancoded_url;

    // allow custom purge functions and stop if they return false
    if (function_exists($this->cleancoded_custom_purge_obj_f)) {
        $f = $this->cleancoded_custom_purge_obj_f;
        if (!$f($cleancoded_url, $cleancoded_host))
            return;
    }

    for ($i = 0; $i < count ($cleancoded_purgeaddr); $i++) {
      $varnish_sock = fsockopen($cleancoded_purgeaddr[$i], $cleancoded_purgeport[$i], $errno, $errstr, $cleancoded_timeout);
      if (!$varnish_sock) {
        error_log("cleancoded-varnish error: $errstr ($errno) on server $cleancoded_purgeaddr[$i]:$cleancoded_purgeport[$i]");
        continue;
      }

      if($cleancoded_use_adminport) {
        $buf = fread($varnish_sock, 1024);
        if(preg_match('/(\w+)\s+Authentication required./', $buf, $matches)) {
          # get the secret
          $secret = $cleancoded_secret[$i];
          fwrite($varnish_sock, "auth " . $this->WPAuth($matches[1], $secret) . "\n");
	  $buf = fread($varnish_sock, 1024);
          if(!preg_match('/^200/', $buf)) {
            error_log("cleancoded-varnish error: authentication failed using admin port on server $cleancoded_purgeaddr[$i]:$cleancoded_purgeport[$i]");
	    fclose($varnish_sock);
	    continue;
	  }
        }
        if ($cleancoded_vversion_optval == 3) {
            $out = "ban req.url ~ ^$cleancoded_url$ && req.http.host == $cleancoded_host\n";
          } else {
            $out = "purge req.url ~ ^$cleancoded_url && req.http.host == $cleancoded_host\n";
          }
      } else {
        $out = "BAN $cleancoded_url HTTP/1.0\r\n";
        $out .= "Host: $cleancoded_host\r\n";
        $out .= "User-Agent: WordPress-Varnish plugin\r\n";
        $out .= "Connection: Close\r\n\r\n";
      }
      fwrite($varnish_sock, $out);
      fclose($varnish_sock);
    }
  }

  function WPAuth($challenge, $secret) {
    $ctx = hash_init('sha256');
    hash_update($ctx, $challenge);
    hash_update($ctx, "\n");
    hash_update($ctx, $secret . "\n");
    hash_update($ctx, $challenge);
    hash_update($ctx, "\n");
    $sha256 = hash_final($ctx);

    return $sha256;
  }
}

$cleancodedVarnish = new cleancodedVarnish();

// Helper functions
function cleanSubmittedData($varname, $regexp) {
// FIXME: should do this in the admin console js, not here   
// normally I hate cleaning data and would rather validate before submit
// but, this fixes the problem in the cleanest method for now
  foreach ($_POST[$varname] as $key=>$value) {
    $_POST[$varname][$key] = preg_replace($regexp,'',$value);
  }
}
?>
