<?php
/**
 * Plugin Name:       WordPress Custom Media Folder Manager
 * Plugin URI:        https://github.com/VitaeLudus/wordpress-custom-media-folder-manager
 * Description:       Allows uploading images into custom subfolders (including nested) in the WordPress media library.
 * Version:           1.2.0
 * Author:            VitaeLudus
 * Author URI:        https://github.com/VitaeLudus
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wordpress-custom-media-folder-manager
 * Domain Path:       /languages
 */

if(!defined('WPINC')){die;}define('CMF_TEXT_DOMAIN','custom-media-folders');define('CMF_OPTION_SUBFOLDERS','cmf_subfolders_list_v2');define('CMF_OPTION_SELECTED_SUBFOLDER','cmf_selected_subfolder_v2');register_activation_hook(__FILE__,'cmf_plugin_activation');function cmf_plugin_activation(){if(false===get_option(CMF_OPTION_SUBFOLDERS)){update_option(CMF_OPTION_SUBFOLDERS,'');}if(false===get_option(CMF_OPTION_SELECTED_SUBFOLDER)){update_option(CMF_OPTION_SELECTED_SUBFOLDER,'');}}function cmf_get_sanitized_folders(){static $sanitized_folders=null;if($sanitized_folders===null){$raw_subfolders_option=get_option(CMF_OPTION_SUBFOLDERS,'');$normalized_subfolders=str_replace("\r\n","\n",$raw_subfolders_option);$defined_subfolders_input_lines=array_filter(array_map('trim',explode("\n",$normalized_subfolders)));$sanitized_folders=[];foreach($defined_subfolders_input_lines as $folder_input_line){$path_segments=explode('/',$folder_input_line);$sanitized_segments=array_map('sanitize_title',$path_segments);$safe_path_value=implode('/',array_filter($sanitized_segments));if(!empty($safe_path_value)){$sanitized_folders[$folder_input_line]=$safe_path_value;}}}return $sanitized_folders;}function cmf_add_media_submenu_page(){add_media_page(esc_html__('Custom Media Folders Settings',CMF_TEXT_DOMAIN),esc_html__('Custom Folders',CMF_TEXT_DOMAIN),'manage_options','cmf_settings','cmf_render_settings_page');}add_action('admin_menu','cmf_add_media_submenu_page');function cmf_render_settings_page(){if(!current_user_can('manage_options')){wp_die(esc_html__('You do not have sufficient permissions to access this page.',CMF_TEXT_DOMAIN));}$raw_subfolders_option=get_option(CMF_OPTION_SUBFOLDERS,'');$normalized_subfolders=str_replace("\r\n","\n",$raw_subfolders_option);$defined_subfolders_input_lines=array_filter(array_map('trim',explode("\n",$normalized_subfolders)));$selected_subfolder_path=get_option(CMF_OPTION_SELECTED_SUBFOLDER,'');$sanitized_folders=cmf_get_sanitized_folders(); ?>
    <div class="wrap">
        <h1><?php esc_html_e('Custom Media Folders Settings',CMF_TEXT_DOMAIN); ?></h1>

        <?php settings_errors('cmf_settings_notices'); ?>

        <form method="post" action=""> <?php  ?>
            <?php wp_nonce_field('cmf_save_settings_nonce','cmf_settings_nonce_field'); ?>
            <input type="hidden" name="page" value="cmf_settings" />

            <table class="form-table" role="presentation">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="cmf_subfolders_list_input"><?php esc_html_e('Define Subfolder Paths',CMF_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <textarea id="cmf_subfolders_list_input" name="cmf_subfolders_list_input" rows="5" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n",$defined_subfolders_input_lines)); ?></textarea>
                            <p class="description"><?php esc_html_e('Enter desired subfolder paths, one per line. e.g., "projects/client-a", "blog-images", "photos/landscapes". Each segment will be sanitized.',CMF_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="cmf_selected_folder_path"><?php esc_html_e('Active Upload Subfolder Path',CMF_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <?php if(!empty($sanitized_folders)): ?>
                                <select id="cmf_selected_folder_path" name="cmf_selected_folder_path">
                                    <option value=""><?php esc_html_e('-- Use WordPress Default (Year/Month) --',CMF_TEXT_DOMAIN); ?></option>
                                    <?php foreach($sanitized_folders as $original_path=>$sanitized_path): ?>
                                        <option value="<?php echo esc_attr($sanitized_path); ?>" <?php selected($sanitized_path,$selected_subfolder_path); ?>>
                                            <?php echo esc_html($original_path); ?> (<?php printf(esc_html__('path: %s',CMF_TEXT_DOMAIN),'<code>'.esc_html($sanitized_path).'</code>'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Select a path to use for new uploads. If none is selected, WordPress default (year/month based) will be used.',CMF_TEXT_DOMAIN); ?></p>
                                
                                <?php $create_test_nonce=wp_create_nonce('cmf_test_folder_access'); ?>
                                <div class="cmf-folder-test-container" style="margin-top: 15px;">
                                    <button type="button" id="cmf-test-folder-access" class="button" 
                                            data-nonce="<?php echo esc_attr($create_test_nonce); ?>">
                                        <?php esc_html_e('Test Folder Access',CMF_TEXT_DOMAIN); ?>
                                    </button>
                                    <span id="cmf-test-result" style="margin-left: 10px; display: inline-block;"></span>
                                </div>

                            <?php else: ?>
                                <p><?php esc_html_e('No subfolder paths defined yet. Please define them above and save.',CMF_TEXT_DOMAIN); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if(!empty($sanitized_folders)): ?>
            <!-- Folder Tree Visualization -->
            <h2><?php esc_html_e('Folder Structure Visualization',CMF_TEXT_DOMAIN); ?></h2>
            <div class="cmf-folder-tree-container" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                <?php echo cmf_render_folder_tree($sanitized_folders); ?>
            </div>
            <?php endif; ?>

            <?php submit_button(esc_html__('Save Settings',CMF_TEXT_DOMAIN)); ?>
        </form>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#cmf-test-folder-access').on('click', function() {
            var button = $(this);
            var resultSpan = $('#cmf-test-result');
            
            button.prop('disabled', true);
            resultSpan.html('<?php esc_html_e('Testing...',CMF_TEXT_DOMAIN); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cmf_test_folder_access',
                    folder: $('#cmf_selected_folder_path').val(),
                    security: button.data('nonce')
                },
                success: function(response) {
                    if (response.success) {
                        resultSpan.html('<span style="color: green;">' + response.data.message + '</span>');
                    } else {
                        resultSpan.html('<span style="color: red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    resultSpan.html('<span style="color: red;"><?php esc_html_e('Test failed - server error',CMF_TEXT_DOMAIN); ?></span>');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php }function cmf_render_folder_tree($sanitized_folders){if(empty($sanitized_folders)){return '<p>'.esc_html__('No folders defined',CMF_TEXT_DOMAIN).'</p>';}$tree=[];foreach($sanitized_folders as $original_path=>$sanitized_path){$parts=explode('/',$sanitized_path);$current=&$tree;foreach($parts as $part){if(!isset($current[$part])){$current[$part]=[];}$current=&$current[$part];}}$html='<div class="cmf-tree">';$html.='<div class="cmf-tree-root"><span class="dashicons dashicons-portfolio"></span> '.esc_html__('uploads',CMF_TEXT_DOMAIN).'</div>';$html.=cmf_render_folder_tree_branch($tree,1);$html.='</div>';$html.='
    <style>
        .cmf-tree { font-family: monospace; }
        .cmf-tree-root { font-weight: bold; margin-bottom: 5px; }
        .cmf-tree-branch { margin-left: 20px; }
        .cmf-tree-item { margin: 3px 0; }
        .cmf-tree-item .dashicons { font-size: 14px; line-height: 1.3; }
    </style>';return $html;}function cmf_render_folder_tree_branch($branch,$level){if(empty($branch)){return '';}$html='<div class="cmf-tree-branch">';foreach($branch as $folder=>$children){$html.='<div class="cmf-tree-item">';$html.='<span class="dashicons dashicons-category"></span> '.esc_html($folder);$html.=cmf_render_folder_tree_branch($children,$level+1);$html.='</div>';}$html.='</div>';return $html;}function cmf_test_folder_access_ajax(){if(!isset($_POST['security'])||!wp_verify_nonce($_POST['security'],'cmf_test_folder_access')){wp_send_json_error(['message'=>__('Security check failed.',CMF_TEXT_DOMAIN)]);}if(!current_user_can('manage_options')){wp_send_json_error(['message'=>__('Permission denied.',CMF_TEXT_DOMAIN)]);}$folder=isset($_POST['folder'])?sanitize_text_field($_POST['folder']):'';$upload_dir=wp_upload_dir();if(empty($folder)){$test_path=$upload_dir['basedir'];$path_label=__('default WordPress upload directory',CMF_TEXT_DOMAIN);}else{$test_path=$upload_dir['basedir'].'/'.$folder;$path_label=sprintf(__('custom folder: %s',CMF_TEXT_DOMAIN),$folder);}if(file_exists($test_path)&&is_dir($test_path)){$dir_exists=true;}else{$dir_exists=wp_mkdir_p($test_path);}if(!$dir_exists){wp_send_json_error(['message'=>sprintf(__('Cannot create %s. Please check directory permissions.',CMF_TEXT_DOMAIN),$path_label)]);}$temp_file=$test_path.'/cmf-test-'.time().'.txt';$write_test=@file_put_contents($temp_file,'Test file. Safe to delete.');if($write_test===false){wp_send_json_error(['message'=>sprintf(__('Directory exists but is not writable: %s',CMF_TEXT_DOMAIN),$path_label)]);}@unlink($temp_file);wp_send_json_success(['message'=>sprintf(__('Success! Directory is accessible and writable: %s',CMF_TEXT_DOMAIN),$path_label)]);}add_action('wp_ajax_cmf_test_folder_access','cmf_test_folder_access_ajax');function cmf_save_settings(){if(!isset($_POST['submit'],$_POST['cmf_settings_nonce_field'],$_POST['page'])||$_POST['page']!=='cmf_settings'){return;}if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cmf_settings_nonce_field'])),'cmf_save_settings_nonce')){wp_die(esc_html__('Nonce verification failed!',CMF_TEXT_DOMAIN),esc_html__('Error',CMF_TEXT_DOMAIN),array('response'=>403));}if(!current_user_can('manage_options')){wp_die(esc_html__('You do not have sufficient permissions to save these settings.',CMF_TEXT_DOMAIN),esc_html__('Error',CMF_TEXT_DOMAIN),array('response'=>403));}if(isset($_POST['cmf_subfolders_list_input'])){$raw_subfolders_input=sanitize_textarea_field(wp_unslash($_POST['cmf_subfolders_list_input']));update_option(CMF_OPTION_SUBFOLDERS,$raw_subfolders_input);}$selected_path_to_save='';if(isset($_POST['cmf_selected_folder_path'])){$potential_selected_path=sanitize_text_field(wp_unslash($_POST['cmf_selected_folder_path']));$sanitized_folders=cmf_get_sanitized_folders();$valid_sanitized_paths=array_values($sanitized_folders);if(!empty($potential_selected_path)&&in_array($potential_selected_path,$valid_sanitized_paths,true)){$selected_path_to_save=$potential_selected_path;}}update_option(CMF_OPTION_SELECTED_SUBFOLDER,$selected_path_to_save);add_settings_error('cmf_settings_notices','cmf_settings_saved',esc_html__('Settings saved successfully.',CMF_TEXT_DOMAIN),'updated');}add_action('admin_init','cmf_save_settings');function cmf_custom_upload_dir_filter($uploads){$selected_path_option=get_option(CMF_OPTION_SELECTED_SUBFOLDER,'');if(!empty($selected_path_option)){$path_segments=explode('/',$selected_path_option);$clean_final_segments=[];foreach($path_segments as $segment){$clean_segment=sanitize_file_name($segment);if(!empty($clean_segment)&&$clean_segment!=='.'&&$clean_segment!=='..'){$clean_final_segments[]=$clean_segment;}}$final_safe_path=implode('/',$clean_final_segments);if(!empty($final_safe_path)){$new_subdir='/'.$final_safe_path;$original_path=$uploads['path'];$original_subdir=$uploads['subdir'];$original_url=$uploads['url'];$uploads['subdir']=$new_subdir;$uploads['path']=$uploads['basedir'].$new_subdir;$uploads['url']=$uploads['baseurl'].$new_subdir;if(!file_exists($uploads['path'])){if(!wp_mkdir_p($uploads['path'])){error_log('Custom Media Folder: Failed to create directory: '.$uploads['path']);$uploads['path']=$original_path;$uploads['subdir']=$original_subdir;$uploads['url']=$original_url;$error_message_context=sprintf(esc_html__('Custom Media Folder: Unable to create directory "%1$s". Please check permissions for the parent directory: %2$s. Uploads will use the default path.',CMF_TEXT_DOMAIN),esc_html($final_safe_path),'<code>'.esc_html($uploads['basedir']).'</code>');if(is_admin()){static $cmf_dir_error_shown=false;if(!$cmf_dir_error_shown){add_action('admin_notices',function()use($error_message_context){printf('<div class="notice notice-error"><p>%s</p></div>',wp_kses_post($error_message_context));});$cmf_dir_error_shown=true;}}$uploads['error']=$error_message_context;}}}}return apply_filters('cmf_modified_upload_dir',$uploads);}add_filter('upload_dir','cmf_custom_upload_dir_filter',20);function cmf_display_current_upload_path_notice(){$upload_dir_info=wp_upload_dir();$selected_path_option=get_option(CMF_OPTION_SELECTED_SUBFOLDER,'');$final_safe_selected_path='';if(!empty($selected_path_option)){$path_segments=explode('/',$selected_path_option);$clean_final_segments=[];foreach($path_segments as $segment){$clean_segment=sanitize_file_name($segment);if(!empty($clean_segment)&&$clean_segment!=='.'&&$clean_segment!=='..'){$clean_final_segments[]=$clean_segment;}}$final_safe_selected_path=implode('/',$clean_final_segments);}$current_upload_subdir=trim($upload_dir_info['subdir'],'/');$is_custom_path_active=!empty($final_safe_selected_path)&&($current_upload_subdir===$final_safe_selected_path);$path_display_for_notice=str_replace(ABSPATH,'',$upload_dir_info['path']);if(empty($path_display_for_notice)||$path_display_for_notice===$upload_dir_info['path']){$path_display_for_notice='wp-content/uploads/'.trim($upload_dir_info['subdir'],'/');$path_display_for_notice=rtrim($path_display_for_notice,'/');}if($is_custom_path_active&&empty($upload_dir_info['error'])){$message=sprintf(esc_html__('New media will be uploaded to your custom path: %s',CMF_TEXT_DOMAIN),'<code>'.esc_html($path_display_for_notice).'</code>');$notice_class='notice-info';}elseif(!empty($upload_dir_info['error'])){$message=sprintf(esc_html__('Error with custom path. New media will use default WordPress path: %s',CMF_TEXT_DOMAIN),'<code>'.esc_html($path_display_for_notice).'</code><br><small>'.esc_html($upload_dir_info['error']).'</small>');$notice_class='notice-warning';}else{$message=sprintf(esc_html__('New media will use the default WordPress path: %s',CMF_TEXT_DOMAIN),'<code>'.esc_html($path_display_for_notice).'</code>');$notice_class='notice-info';}printf('<div class="notice %s inline"><p>%s</p></div>',esc_attr($notice_class),wp_kses_post($message));}add_action('post-plupload-upload-ui','cmf_display_current_upload_path_notice');add_action('pre-plupload-upload-ui','cmf_display_current_upload_path_notice');function cmf_load_textdomain(){load_plugin_textdomain(CMF_TEXT_DOMAIN,false,dirname(plugin_basename(__FILE__)).'/languages/');}add_action('plugins_loaded','cmf_load_textdomain');function cmf_add_settings_link($links){$settings_link='<a href="'.admin_url('upload.php?page=cmf_settings').'">'.esc_html__('Settings',CMF_TEXT_DOMAIN).'</a>';array_unshift($links,$settings_link);return $links;}add_filter('plugin_action_links_'.plugin_basename(__FILE__),'cmf_add_settings_link');function cmf_admin_enqueue_scripts($hook){if($hook!=='media_page_cmf_settings'&&$hook!=='upload.php'){return;}wp_enqueue_style('dashicons');}add_action('admin_enqueue_scripts','cmf_admin_enqueue_scripts');
