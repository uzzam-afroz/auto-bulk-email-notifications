<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://rehan.work
 * @since      2.1.0
 *
 * @package    Aben
 * @subpackage Aben/admin/partials
 */

class Aben_Email
{

    private $email_subject;
    private $archive_page_slug;
    private $number_of_posts;
    private $unsubscribe_link;
    private $body_bg;
    private $header_text;
    private $header_bg;
    private $header_subtext;
    private $footer_text;
    private $site_logo;
    private $show_view_all;
    private $view_all_posts_text;
    private $show_number_view_all;
    private $show_view_post;
    private $view_post_text;
    private $show_unsubscribe;
    private $posts_to_send;

    public function __construct(
        $archive_page_slug,
        $number_of_posts,
        $body_bg,
        $header_text,
        $header_bg,
        $header_subtext,
        $footer_text,
        $site_logo,
        $show_view_all,
        $view_all_posts_text,
        $show_view_post,
        $view_post_text,
        $show_unsubscribe,
        $posts_to_send
    ) {
        $this->archive_page_slug   = $archive_page_slug;
        $this->number_of_posts     = $number_of_posts;
        $this->body_bg             = $body_bg;
        $this->header_text         = $header_text;
        $this->header_bg           = $header_bg;
        $this->header_subtext      = $header_subtext;
        $this->footer_text         = $footer_text;
        $this->site_logo           = $site_logo;
        $this->show_view_all       = $show_view_all;
        $this->view_all_posts_text = $view_all_posts_text;
        $this->show_view_post      = $show_view_post;
        $this->view_post_text      = $view_post_text;
        $this->show_unsubscribe    = $show_unsubscribe;
        $this->posts_to_send       = $posts_to_send;
    }

    /** Check for GW Add On is activated or not */
    public function is_aben_gw_active()
    {
        // Get the list of active plugins
        $active_plugins = get_option('active_plugins');
        return in_array('aben-gw-add-on/aben-gw.php', $active_plugins);
    }

    public static function is_pro()
    {
        return aben_get_options()['pro'];
    }

    public function aben_email_template()
    {

        $site_icon_url = get_site_icon_url();
        $logo          = empty($this->site_logo) ? $site_icon_url : $this->site_logo;
        echo '<!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title></title></head>
        <body>
        <div id="aben-email-template" style="font-family:Open Sans,sans-serif;margin:0;padding:0;background: ' . esc_attr($this->body_bg) . ';color: #1f2430;">
        <div style="width:100%;max-width:500px;margin: auto;">
        <div style="padding: 50px 30px 30px 30px;">
        <p id ="header-text" style="font-size:16px;display: inline;"><strong>' . esc_html($this->header_text) . '</strong></p>';
        do_action('aben_after_header_text'); // After Header Text Hook
        echo '<img width="16px" src="' . esc_url(ABEN_PLUGIN_URL . 'assets/images/hand-emoji.png') . '" style="
    margin-left: 5px">
        <p id="header-subtext" style="font-size:16px;">' . esc_html($this->header_subtext) . '</p>';
        do_action('aben_after_header_sub_text'); //After Header Sub Text Hook
        echo '</div>
        <div id="posts-wrapper"">';
        do_action('aben_before_posts_loop'); // Before Posts Loop Hook
        $ad_tile_location = 0;
        foreach ($this->posts_to_send as $post) {
            if ($this->number_of_posts <= 0) {
                break;
            }

            do_action('aben_within_posts_loop', $this->number_of_posts); // Within Posts Loop Hook

            $filterable_fields = ['title', 'link', 'excerpt', 'featured_image_url', 'author'];

            foreach ($filterable_fields as $field) {
                if (isset($post[$field])) {
                    $post[$field] = apply_filters("aben_post_{$field}_filter", $post[$field], $post['id'] ?? null);
                }
            }

            $id           = $post['id'] ?? null;
            $author_id    = $post['author'] ?? null;
            $title        = $post['title'];
            $link         = $post['link'];
            $excerpt      = $post['excerpt'];
            $category     = $post['category'];
            $category_csv = ! empty($category) ? implode(', ', $category) : null;
            $image        = $post['featured_image_url'];

            $excerpt_width = $this->show_view_post && $this->is_aben_gw_active() ? 60 : ($this->show_view_post || $this->is_aben_gw_active() ? 85 : 100);

            echo '<div class="post-tile" style="margin-bottom:20px;padding:25px; border-radius:3px; background:' . esc_attr($this->header_bg) . ';">';
            echo '<div style="margin-bottom:10px; display:flex; column-gap:10px;">';
            echo '<div>';
            if ($category_csv) {
                echo '<p style="margin:0; color:#727272; line-height:1; font-style:italic;">' . esc_html($category_csv) . '</p>';
            }
            echo '</div></div>';
            echo '<div style="display:flex">';
            if ($this->show_view_post && ! empty($image)) {
                echo '<div class="view-post" style="width:15%;margin-right: 25px;align-self:center; margin-top:auto; margin-bottom:auto;"><a href="' . esc_url($link) . '"><img width="100%" max-width="100px" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '" /></a></div>';
            }
            echo '<div style="width:' . esc_attr($excerpt_width) . '%;"><p style="font-size:16px;margin:0;color: #008dcd;"><a href="' . esc_url($link) . '" style="text-decoration:none;">' . esc_html($title) . '</a></p>';
            if (! empty($excerpt)) {
                echo '<p style="font-size:14px;color:#727272;margin:5px 0 0">' . esc_html($excerpt) . '</p>';
            }
            echo '</div>';
            do_action('aben_post_button_hook', $link); // Post Button Hook
            echo '</div></div>';

            $this->number_of_posts--;
        }
        do_action('aben_after_posts_loop'); // After Posts Loop Hook
        echo '<div style="display:flex; border-radius:3px; overflow:hidden">
        <div style="width:100%;text-align:center;">';
        if ($this->show_view_all) {
            echo '<a id="view-all-post" href="' . esc_url($this->archive_page_slug) . '"style="display:inline-block;padding:15px 0px;background-color:#2271b1;color:#ffffff;text-decoration:none;width: 100%;font-size:16px;">' . esc_html($this->view_all_posts_text) . '</a>';
        }
        echo '</div></div></div>
        <div style="color:#808080;text-align:center;padding: 30px 30px 50px 30px;">
        <a href="' . esc_url(home_url()) . '"><img src="' . esc_url($logo) . '" alt="Site Logo" style="max-height:40px; object-fit:contain; margin-top: 10px;"></a><div>';
        do_action('aben_before_footer_text'); // Before Footer Text Hook
        echo '</div><p id="footer-text">' . esc_html($this->footer_text) . '</p><div>';
        do_action('aben_after_footer_text'); // After Footer Text Hook
        if ($this->show_unsubscribe) {
            echo '<span id="unsubscribe"><a href="' . esc_html(home_url('?aben-unsubscribe={{USER_EMAIL}}')) . '" style="color:#808080;text-decoration:none">Unsubscribe</a></span>';
        }
        if (! self::is_pro()) {
            echo '</div><p><a href="' . esc_url(ABEN_BRAND_LINK) . '" style="text-decoration:none;">' . esc_html(ABEN_BRAND_TEXT) . ' <img src="' . esc_url(ABEN_PLUGIN_LOGO) . '" width="60px" alt="Aben" style="margin-bottom:-4px"/></a></p>';
            echo '</div></div></body></html>';
        }
    }
}
