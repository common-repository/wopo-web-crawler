<?php
/**
 * Plugin Name:       WoPo Web Crawler
 * Plugin URI:        https://wopoweb.com/product/wopo-web-crawler-pro/
 * Description:       Get content from other websites
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            WoPo Web
 * Author URI:        https://wopoweb.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wopo-web-crawler
 * Domain Path:       /languages
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('WOPOWC_UPLOAD_DIR', wp_upload_dir()['basedir'].'/wopo-web-crawler/');

function wopowc_settings_init() {
    // Register a new setting for "wopowc" page.
    register_setting( 'wopowc', 'wopowc_options' );
 
    // Register a new section in the "wopowc" page.
    add_settings_section(
        'wopowc_section_crawler',
        __( 'WoPo Web', 'wopo-web-crawler' ), 
        'wopowc_section_crawler_callback',
        'wopowc',
        array(
            'label_for' => 'wopowc_wopo_web',
        )
    );
 
    // Register a new field in the "wopowc_section_developers" section, inside the "wopowc" page.
    $settings = array(
        array(
            'label_for' => 'wopowc_category_url',
            'title' => __( 'Category URL', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'Enter category URL to crawl.', 'wopo-web-crawler' )
        ),
        array(
            'label_for' => 'wopowc_page_selector',
            'title' => __( 'Details URL Selector', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'Xpath selector for detail page', 'wopo-web-crawler' ),
            'button' => array(
                'name' => 'check_cat_url',
                'value' => __( 'Test Selector', 'wopo-web-crawler' ),
            ),
        ),
        array(
            'label_for' => 'wopowc_page_title_selector',
            'title' => __( 'Details Title Selector', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'Xpath selector for details page title', 'wopo-web-crawler' ),
            'button' => array(
                'name' => 'check_detail_title',
                'value' => __( 'Test Selector', 'wopo-web-crawler' ),
            ),
        ),
        array(
            'label_for' => 'wopowc_page_content_selector',
            'title' => __( 'Details Content Selector', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'Xpath selector for details page content', 'wopo-web-crawler' ),
            'button' => array(
                'name' => 'check_detail_content',
                'value' => __( 'Test Selector', 'wopo-web-crawler' ),
            ),
        ),
        array(
            'label_for' => 'wopowc_next_selector',
            'title' => __( 'Next page URL Selector', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'Xpath selector for next page button link', 'wopo-web-crawler' ),
            'button' => array(
                'name' => 'check_next_url',
                'value' => __( 'Test Selector', 'wopo-web-crawler' ),
            ),
            'pro_version' => true,
        ),
        array(
            'label_for' => 'wopowc_post_status',
            'title' => __( 'New Post Status', 'wopo-web-crawler' ),
            'class' => 'large-text',
            'description' => esc_html__( 'This is a customizable option for the post status of newly created content after content crawling.', 'wopo-web-crawler' ),
            'pro_version' => true,
            'html_tag' => array(
                'name' => 'select',
                'options' => array(
                    'draft' => 'Draft',
                    'pending' => 'Pending',
                    'publish' => 'Publish'
                )
            )
        )
    );

    foreach($settings as $st){
        add_settings_field(
            $st['label_for'], 
            $st['title'],
            'wopowc_field_setting_cb',
            'wopowc',
            'wopowc_section_crawler',
            $st                 
        );  
    }
}
 
/**
 * Register our wopowc_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'wopowc_settings_init' );
 
 
/**
 * Custom option and settings:
 *  - callback functions
 */
 
 
/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function wopowc_section_crawler_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['label_for'] ?? '' ); ?>">
        <?php esc_html_e( 'Thank you for using my plugin.  If you encounter any issues or have suggestions for my plugin, you can contact me at the following link:', 'wopo-web-crawler' ); ?>
		<a target="_blank" href="https://wopoweb.com/contact-us/">https://wopoweb.com/contact-us/</a>
    </p>
    <?php
    $options = get_option( 'wopowc_options' );
    if (isset($options['start_crawling'])){
        ?>
        <h2>Crawler logs</h2>
        <p>
            <ol>
                <?php
                $options['start_crawling'] = null;
                update_option('wopowc_options',$options);
                $categoryUrl = $options['wopowc_category_url'];
                $doc = new DOMDocument();
                do{
                    $urlPath = parse_url( $categoryUrl);
                    $domain = $urlPath['host'];
                    $categoryDir = WOPOWC_UPLOAD_DIR. $domain.'/category/';
                    if (!file_exists($categoryDir)){
                        wp_mkdir_p($categoryDir);
                        chmod($categoryDir, 0777);
                    }
                    $categoryFile = $categoryDir.sanitize_file_name($categoryUrl) . '.html';
                    if (!file_exists($categoryFile)){
                        $file = download_url($categoryUrl);
                        copy($file, $categoryFile);
                        unlink($file);
                    }
                    $html = file_get_contents($categoryFile);
                    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
                    $xpath = new DOMXpath($doc);
                    // category is false by default to stop loop
                    $categoryUrl = apply_filters( 'wopowc_category_url', false, $xpath );

                    $links = $xpath->query($options['wopowc_page_selector']);
                    for($i = 0; $i < $links->length; $i++){
                        $detailsLink = $links->item($i)->nodeValue;
                        $detailsDir = WOPOWC_UPLOAD_DIR. $domain.'/details/';
                        if (!file_exists($detailsDir)){
                            wp_mkdir_p($detailsDir);
                            chmod($detailsDir, 0777);
                        }
                        $detailsFile = $detailsDir.sanitize_file_name($detailsLink) . '.html';
                        if (!file_exists($detailsFile)){
                            $file = download_url($detailsLink);
                            copy($file, $detailsFile);
                            unlink($file);
                        }
                        $html = file_get_contents($detailsFile);
                        $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
                        $xpath = new DOMXpath($doc);
                        $pageTitle = $xpath->query($options['wopowc_page_title_selector'])->item(0)->nodeValue;
                        
                        $pageContent = $xpath->query($options['wopowc_page_content_selector']);
                        $htmlContent = $doc->saveHTML($pageContent->item(0));

                        $post_data = apply_filters( 'wopowc_before_insert_post', array(
                            'post_title' => $pageTitle,
                            'post_content' => $htmlContent,
                        ));
                        $pid = wp_insert_post($post_data);
                        if(!is_wp_error( $pid )){
                            $plink = get_the_permalink( $pid );
                            echo "<li>". esc_html($pageTitle) . ' imported! Post URL: <a target="_blank" href="'. esc_url($plink) .'">'.esc_html($plink).'</a></li>';
                        }else{
                            echo "<li>". esc_html($pageTitle) . ' import error. Please kindly contact us for get help.</li>';
                            break;
                        }
                        ob_flush();
                        flush();
                    }
                }while($categoryUrl);
                ?>
            </ol>
        </p>
        <?php
    }
}
 
/**
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function wopowc_field_setting_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'wopowc_options' );
    $is_pro_active = !isset($args['pro_version']) || defined('WOPO_WCP');
    if (isset($args['html_tag']['name']) && $args['html_tag']['name'] == 'select'){
        ?>
        <select id="<?php echo esc_attr( $args['label_for'] ?? '' ); ?>" 
                <?php echo !$is_pro_active ? ' disabled ' : '' ?>
                name="wopowc_options[<?php echo esc_attr( $args['label_for'] ?? '' ); ?>]">
            <?php
            $selected = esc_attr($options[ $args['label_for'] ] ?? 'draft');
            foreach($args['html_tag']['options'] as $key => $value){
                echo '<option value="'.esc_attr($key).'" '.($selected == $key ? 'selected': '').'>'.esc_html($value).'</option>';
            }
            ?>
        </select>
        <?php 
    }else{
        ?>
        <input id="<?php echo esc_attr( $args['label_for'] ?? '' ); ?>"
                value="<?php echo esc_attr($options[ $args['label_for'] ] ?? '') ?>"
                class="<?php echo esc_attr($args['class'] ?? '' ) ?>"
                <?php echo !$is_pro_active ? ' disabled placeholder="Pro version required"' : '' ?>
                name="wopowc_options[<?php echo esc_attr( $args['label_for'] ?? '' ); ?>]" />
        <?php 
    }
    ?>
    <p class="description">
        <?php echo esc_html($args['description'])  ?>
        <?php echo  !$is_pro_active ? '<span style="color:red">Pro Version</span>' : '' ?>
    </p>
    <?php
    if (isset($args['button']) && $is_pro_active){
    ?>
        <input name="wopowc_options[<?php echo esc_attr($args['button']['name']) ?>]" type="submit" class="button button-primary" value="<?php echo esc_attr($args['button']['value']) ?>" />
    <?php
        if ((isset($options['check_cat_url']) || isset($options['check_next_url']) || isset($options['check_detail_title']) || isset($options['check_detail_content'])) && !empty($options['wopowc_category_url'])){
            $urlPath = parse_url( $options['wopowc_category_url']);
            $domain = $urlPath['host'];
            $categoryDir = WOPOWC_UPLOAD_DIR. $domain.'/category/';
            if (!file_exists($categoryDir)){
                wp_mkdir_p($categoryDir);
                chmod($categoryDir, 0777);
            }
            $categoryFile = $categoryDir.sanitize_file_name($options['wopowc_category_url']) . '.html';
            if (!file_exists($categoryFile)){
                $file = download_url($options['wopowc_category_url']);
                copy($file, $categoryFile);
                unlink($file);
            }
            $html = file_get_contents($categoryFile);
            $doc = new DOMDocument();
            $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
            $xpath = new DOMXpath($doc);
            $pageLinks = null;
            if (isset($options['check_cat_url']) 
                    || (
                        (isset($options['check_detail_title']) || isset($options['check_detail_content'])
                    ) && !empty($options['wopowc_page_selector']))){
                $links = $xpath->query($options['wopowc_page_selector']);
                if($args['label_for'] == 'wopowc_page_selector'){
                    $pageLinks = $links;
                }
                if($args['label_for'] == 'wopowc_page_title_selector' || $args['label_for'] == 'wopowc_page_content_selector'){
                    $detailsLink = $links->item(0)->nodeValue;
                    $detailsDir = WOPOWC_UPLOAD_DIR. $domain.'/details/';
                    if (!file_exists($detailsDir)){
                        wp_mkdir_p($detailsDir);
                        chmod($detailsDir, 0777);
                    }
                    $detailsFile = $detailsDir.sanitize_file_name($detailsLink) . '.html';
                    if (!file_exists($detailsFile)){
                        $file = download_url($detailsLink);
                        copy($file, $detailsFile);
                        unlink($file);
                    }
                    $html = file_get_contents($detailsFile);
                    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
                    $xpath = new DOMXpath($doc);
                    if ($args['label_for'] == 'wopowc_page_title_selector'){
                        $pageTitle = $xpath->query($options['wopowc_page_title_selector']);
                        for($i = 0; $i < $pageTitle->length; $i++){
                            echo "<br/>Page Title ". esc_html($i + 1). " : ". esc_html($pageTitle->item($i)->nodeValue);
                        }
                    }
                    if ($args['label_for'] == 'wopowc_page_content_selector'){
                        $pageContent = $xpath->query($options['wopowc_page_content_selector']);
                        for($i = 0; $i < $pageContent->length; $i++){
                            $html = $doc->saveHTML($pageContent->item($i));
                            echo "<br/>Page Content ". esc_html($i + 1). " : ". wp_kses_post($html);
                        }
                    }
                }
            }
            if (isset($options['check_next_url']) && $args['label_for'] == 'wopowc_next_selector'){
                $pageLinks = $xpath->query($options['wopowc_next_selector']);                
            }
            if(isset($pageLinks)){
                for($i = 0; $i < $pageLinks->length; $i++){
                    echo "<br/>". esc_html($i + 1). " : ". esc_html($pageLinks->item($i)->nodeValue);
                }
            }
        }
        
    }
    
}
 
/**
 * Add the top level menu page.
 */
function wopowc_options_page() {
    add_menu_page(
        'WoPo Web Crawler',
        'WoPo Web Crawler',
        'manage_options',
        'wopowc',
        'wopowc_options_page_html'
    );
}
 
 
/**
 * Register our wopowc_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'wopowc_options_page' );
 
 
/**
 * Top level menu callback function
 */
function wopowc_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
 
    // add error/update messages
 
    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'wopowc_messages', 'wopowc_message', __( 'Settings Saved', 'wopo-web-crawler' ), 'updated' );
    }
 
    // show error/update messages
    settings_errors( 'wopowc_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "wopowc"
            settings_fields( 'wopowc' );
            // output setting sections and their fields
            // (sections are registered for "wopowc", each field is registered to a specific section)
            do_settings_sections( 'wopowc' );
            // output save settings button
            submit_button( 'Save Settings' ,'large','save', false);
            echo "&nbsp;&nbsp;";
            submit_button( 'Start crawling' ,'primary','wopowc_options[start_crawling]', false);
            ?>
        </form>
    </div>
    <?php
}