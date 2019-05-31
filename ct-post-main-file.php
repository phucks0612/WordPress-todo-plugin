<?php
/**
* Plugin Name: Thanh.io Post Plugin
* Plugin URI: https://www.yourwebsiteurl.com/
* Description: This is the very first plugin I ever created.
* Version: 1.0
* Author: Phan Chi Thanh
* Author URI: Thanh.io
**/
class CT_TODO {
    private static $priority_field = "ct_todo_priority";
    private static $startdate_field = "ct_todo_start_date";
    private static $duedate_field = "ct_todo_due_date";
    private static $status_field = "ct_todo_status";

    private static $initiated = false;

    public static function init() {
		if (!self::$initiated) {
			self::init_hooks();
        }
    }
    
    private static function init_hooks() {
        self::$initiated = true;
        add_filter( 'get_sample_permalink_html',function(){return '';});
        add_filter('manage_ct_todo_posts_columns',function ( $columns ) 
        {
            unset($columns['date']);
            return $columns;
        } );

        add_action( 'wp_enqueue_scripts', array('CT_TODO', 'wpdocs_theme_name_scripts') );

        add_action('init', array('CT_TODO', 'ct_todo_post_type'), 1, 1);

        add_action('add_meta_boxes', array('CT_TODO', 'ct_add_todo_box'));

        add_action('save_post', array('CT_TODO', 'ct_save_todo_priority_post_data'));
        add_filter('manage_ct_todo_posts_columns', array('CT_TODO', 'ct_todo_box_priority_column_head'));
        add_filter('manage_edit-ct_todo_sortable_columns', array('CT_TODO', 'ct_todo_priority_column'));
        add_action('pre_get_posts', array('CT_TODO', 'ct_todo_priority_query'));
        add_action('manage_ct_todo_posts_custom_column', array('CT_TODO', 'ct_todo_box_priority_column_content'), 10, 2);

        add_action('save_post', array('CT_TODO', 'ct_save_todo_date_post_data'));
        add_filter('manage_ct_todo_posts_columns', array('CT_TODO', 'ct_todo_box_duedate_column_head'));
        add_action('manage_ct_todo_posts_custom_column', array('CT_TODO', 'ct_todo_box_duedate_column_content'), 10, 2);

        add_action('save_post', array('CT_TODO', 'ct_save_todo_status_post_data'));
        add_filter('manage_ct_todo_posts_columns', array('CT_TODO', 'ct_todo_box_status_column_head'));
        add_action('manage_ct_todo_posts_custom_column', array('CT_TODO', 'ct_todo_box_status_column_content'), 10, 2);

        register_activation_hook(__FILE__, array('CT_TODO', 'my_activation'));
        add_action('my_daily_event', array('CT_TODO', 'do_this_daily'));
        
        add_filter('pre_get_posts', array('CT_TODO', 'posts_for_current_author'));

        add_shortcode( 'todo', array('CT_TODO', 'ct_todo_func' ));
    }

    

    public static function wpdocs_theme_name_scripts() {
        wp_enqueue_script( 'script', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
    }


    public static function ct_todo_post_type()
    {
        register_post_type('ct_todo',
                        array(
                            'labels'      => array(
                                'name'          => __('Todos'),
                                'singular_name' => __('Todo'),
                            ),
                            'public'      => true,
                            'has_archive' => true,
                        )
        );
    }

    public static function ct_add_todo_box()
    {
        add_meta_box(
            'ct_todo_priority_id',           // Unique ID
            'Priority',  // Box title
            array('CT_TODO', 'ct_todo_priority_box_html'),  // Content callback, must be of type callable
            'ct_todo'                 // Post type
        );

        add_meta_box(
            'ct_todo_startdate_id',
            'Date',
            array('CT_TODO', 'ct_todo_date_html'),
            'ct_todo'
        );
        
        add_meta_box(
            'ct_todo_status_id',
            'Status',
            array('CT_TODO', 'ct_todo_status_box_html'),
            'ct_todo'
        );
    }

    //----------------------------------------------------------------------------------------------------------

    public static function ct_todo_priority_box_html()
    {
        $value = get_post_meta($post->ID, self::$priority_field, true);
        ?>
        <html>
        <label for="ct_todo_priority"><?= __('Priority: ') ?></label>
        <select name="ct_todo_priority" id="ct_todo_priority" class="postbox">
            <option value="None"> <?= __('Select something...')?> </option>
            <option value="High" <?php selected($value, 'High'); ?>><?= __('High') ?></option>
            <option value="Normal" <?php selected($value, 'Normal'); ?>><?= __('Normal') ?></option>
            <option value="Low" <?php selected($value, 'Low'); ?>><?= __('Low') ?></option>
        </select>
        </html>
        <?php
    }

    public static function ct_save_todo_priority_post_data($post_id)
    {   
        if (array_key_exists(self::$priority_field, $_POST)) {
            update_post_meta(
                $post_id,
                self::$priority_field,
                $_POST[self::$priority_field]
            );
            $temp = 4;
            if ($_POST[self::$priority_field] == 'High') $temp = 1;
            if ($_POST[self::$priority_field] == 'Normal') $temp = 2;
            if ($_POST[self::$priority_field] == 'Low') $temp = 3;
            update_post_meta(
                $post_id,
                'priority_cheat',
                $temp
            );
        }
    }

    public static function ct_todo_box_priority_column_head($defaults) {
        $defaults['priority'] = __('Priority');
        return $defaults;
    }
    

    public static function ct_todo_priority_column($sortable_columns){
        $sortable_columns['priority'] = 'priority_cheat';
        return $sortable_columns;
    }
    

    public static function ct_todo_priority_query($query) {
        $orderby = $query->get('orderby');
        if('priority_cheat' == $orderby) {
            $query->set('meta_key','priority_cheat');
            $query->set('orderby','meta_value_num');
        }
    }

    public static function ct_todo_box_priority_column_content($column_name, $post_id){
        if ($column_name == 'priority') {
            $temp = get_post_meta($post_id, self::$priority_field, true);
            if ($temp) {
                esc_html_e($temp);
            }
            else esc_html_e('Unavailable');
        }
    }
    //----------------------------------------------------------------------------------------------------------

    public static function ct_todo_date_html()
    {
        global $post;
        $value = get_post_meta($post->ID, self::$startdate_field, true);
        ?>
        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                From:
            </td>
            <td>
                <input type="text" name="ct_todo_start_date" id="txtFrom" />
            </td>
            <td>
                &nbsp;
            </td>
            <td>
                To:
            </td>
            <td>
                <input type="text" name="ct_todo_due_date" id="txtTo" />
            </td>
        </tr>
        </table>  
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
        <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
        <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="Stylesheet"type="text/css"/>
        <script type="text/javascript">
            jQuery(document).ready function() {
                jQuery("#txtFrom").datepicker({
                    minDate: 0,
                    numberOfMonths: 2,
                    dateFormat: 'yy/mm/dd',
                    onSelect: function (selected) {
                        var dt = new Date(selected);
                        dt.setDate(dt.getDate());
                        jQuery("#txtTo").datepicker("option", "minDate", dt);
                    }
                });
                jQuery("#txtTo").datepicker({
                    minDate: 0,
                    numberOfMonths: 2,
                    dateFormat: 'yy/mm/dd',
                    onSelect: function (selected) {
                        var dt = new Date(selected);
                        dt.setDate(dt.getDate());
                        jQuery("#txtFrom").datepicker("option", "maxDate", dt);
                    }
                });
            });
        </script>
        <?php
    }

    public static function ct_save_todo_date_post_data($post_id)
    {   
        if (array_key_exists(self::$startdate_field, $_POST)) {
            update_post_meta(
                $post_id,
                self::$startdate_field,
                $_POST[self::$startdate_field]
            );
        }

        if (array_key_exists(self::$duedate_field, $_POST)) {
            update_post_meta(
                $post_id,
                self::$duedate_field,
                $_POST[self::$duedate_field]
            );
        }
    }
    

    public static function ct_todo_box_duedate_column_head($defaults) {
        $defaults['startdate'] = __('Start Date');
        $defaults['duedate'] = __('Due Date');
        return $defaults;
    }

    public static function ct_todo_box_duedate_column_content($column_name, $post_id){
        if ($column_name == 'startdate') {
            $temp = get_post_meta($post_id, self::$startdate_field, true);
            if ($temp) {
                esc_html_e($temp);
            }
            else esc_html_e('Unavailable');
        }

        if ($column_name == 'duedate') {
            $temp = get_post_meta($post_id, self::$duedate_field, true);
            if ($temp) {
                esc_html_e($temp);
            }
            else esc_html_e('Unavailable');
        }
    }

    //----------------------------------------------------------------------------------------------------------

    public static function ct_todo_status_box_html()
    {
        global $post;
        $value = get_post_meta($post->ID, self::$status_field, true);
        if ($value) {
            ?>
            <label for="ct_todo_status"><?= __('Status: ') ?></label>
            <select name="ct_todo_status" id="ct_todo_status" class="postbox">
                <option value="Pending"><?= __('Pending') ?></option>
                <option value="Doing" <?php selected($value, 'Doing'); ?>><?= __('Doing') ?></option>
                <option value="Done" <?php selected($value, 'Done'); ?>><?= __('Done') ?></option>
                <option value="Overdue" <?php selected($value, 'Overdue'); ?>><?= __('Overdue') ?></option>
            </select>
            <?php
        }
        else {
            ?>
            <label for="ct_todo_status"><?= __('Status: ') ?></label>
            <select name="ct_todo_status" id="ct_todo_status" class="postbox">
                <option value="Pending"><?= __('Pending') ?></option>
            </select>
            <?php
        }
    }



    public static function ct_save_todo_status_post_data($post_id)
    {    
        if (array_key_exists(self::$status_field, $_POST)) {
            update_post_meta(
                $post_id,
                self::$status_field,
                $_POST[self::$status_field]
            );
        }
    }

    public static function ct_todo_box_status_column_head($defaults) {
        $defaults['status'] = __('Status');
        return $defaults;
    }

    public static function ct_todo_box_status_column_content($column_name, $post_id){
        if ($column_name == 'status') {
            $temp = get_post_meta($post_id, self::$status_field, true);
            if ($temp) {
                esc_html_e($temp);
            }
            else esc_html_e('Unavailable');
        }
    }

    //----------------------------------------------------------------------------------------------------------

    

    public static function my_activation() {
        if (! wp_next_scheduled ( 'my_daily_event' )) {
            wp_schedule_event(time(), 'daily', 'my_daily_event');
        }
    }

    public static function do_this_daily() {
        $args = array('numberposts' => -1, 'post_type' => 'ct_todo', 'post_status' => 'any');
        $posts = get_posts($args);
        foreach ($posts as $x) {
            $y = get_post_meta($x->ID,$duedate_field,true);
            if ($y['ct_todo_due_date'][0] > date('y/m/d')) {
                $k = get_userdata($x->post_author);
                update_post_meta(
                    $x->ID,
                    self::$status_field,
                    'Overdue'
                );
                wp_mail($k->user_email,'testing','HelloWorld');
            }
        }
    }

    //----------------------------------------------------------------------------------------------------------

    public static function posts_for_current_author($query) {
        global $pagenow;

        if( 'edit.php' != $pagenow || !$query->is_admin )
            return $query;

        if( !current_user_can( 'manage_options' ) ) {
            global $user_ID;
            $query->set('author', $user_ID );
        }
        return $query;
    }

    //----------------------------------------------------------------------------------------------------------

    public static function ct_todo_func($atts) {
        $u = wp_get_current_user();
        if ($atts['status']) 
            $status = explode(",", $atts['status']);
        else 
            $status = array('Pending','Doing','Done','Overdue');

        if ($atts['order'])
            $order = explode(",",$atts['order']);
        else
            $order = array('date','status');

        for ($i=0; $i<count($order); $i++) {
            if ($order[$i] == 'priority') $order[$i] = 'priority_cheat';
            if ($order[$i] == 'status') $order[$i] = $status_field;
        }

        $args = array(
            'numberposts' => -1, 
            'post_type' => 'ct_todo', 
            'post_status' => 'any', 
            'orderby' => $orders, 
            'order'=> 'DESC', 
            'author'=> $u->ID,
            'meta_value'=> $status
        );
        $posts = get_posts($args);
        $i=0;    
        foreach ($posts as $post) {
            $i++;
            $ans .= "{$i} {$post->post_title}: Date: {$post->post_date}, Status: {$post->ct_todo_status}, Priority: {$post->ct_todo_priority} <br>";
        }
        if (!$ans) return 'Nothing to do'; else return $ans;
    }
}

new CT_TODO();
CT_TODO::init();