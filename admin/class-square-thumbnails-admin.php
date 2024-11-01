<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://ilmdesigns.com/
 * @since      1.0.0
 *
 * @package    Square_Thumbnails
 * @subpackage Square_Thumbnails/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Square_Thumbnails
 * @subpackage Square_Thumbnails/admin
 * @author     ILMDESIGNS <narcisbodea@gmail.com>
 */


class Square_Thumbnails_Admin {

    private $plugin_name;
    private $version;
    private $option_name = 'square_thumbnails';

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.'));
        }
        add_submenu_page('upload.php',
            'Square Thumbnails Options',
            'Square Thumbnails',
            'manage_options',
            'square-thumbnails-admin-page',
            array($this, 'showPage'),
            '',
            '3.0'
        );
        do_action('square-thumbnails-settings');
    }

    public function donation_notice() {
        $coffee = 'https://buymeacoffee.com/narcisbodea'; 
        $revolut = 'https://revolut.me/nicunatymj'; 
        $paypal = 'https://paypal.me/narcisbodea'; 
        $message = sprintf(__('If you find <b>Square Thumbnails</b> plugin useful, please consider making a donation to support its development and support. <a href="%1$s" target="_blank">Click here to donate by paypal.</a> or <a href="%2$s" target="_blank">Click here to donate by revolut.</a> or <a href="%3$s" target="_blank">Click here to buy me a coffee.</a>', 'utxi-textdomain'), $paypal, $revolut, $coffee);
        echo '<div class="notice notice-info"><p>' . wp_kses_post($message) . '</p></div>';
    }

    public function link_settings( $links ) {
        $links[] = sprintf('<a href="%s">%s</a>', esc_url(admin_url('upload.php?page=square-thumbnails-admin-page')), esc_html__('Settings', 'square-thumbnails'));
        return $links;
    }

    public function showPage() {
        include 'partials/square-thumbnails-admin-display.php';
    }

    private function getColor($im) {
        $getimbg = get_option($this->option_name . '_getimcolor');
        if (!empty($getimbg)) {
            $rgb = imagecolorat($im, 0, 0);
            $colors = imagecolorsforindex($im, $rgb);
            $red = $colors['red'];
            $green = $colors['green'];
            $blue = $colors['blue'];
        } else {
            $red = 255;
            $green = 255;
            $blue = 255;
            $htmlcolor = get_option($this->option_name . '_bgcolor');
            $ret = $this->hex2RGB($htmlcolor);
            $red = $ret['red'];
            $green = $ret['green'];
            $blue = $ret['blue'];
        }

        return array('red' => $red, 'green' => $green, 'blue' => $blue);
    }

    private function getPaths($filename) {
        $updir = wp_upload_dir();
        $file = trailingslashit($updir['basedir']) . $filename;
        $dir = trailingslashit(dirname($file));
        $path = new stdClass();
        $path->upload = $updir;
        $path->file = $file;
        $path->dir = $dir;
        return $path;
    }

    private function createIm($mime, $file, &$im) {
        switch ($mime) {
            case 'image/png':
                $im = imagecreatefrompng($file);
                break;
            case 'image/jpeg':
                $im = imagecreatefromjpeg($file);
                break;
            case 'image/gif':
                $im = imagecreatefromgif($file);
                break;
            case 'image/bmp':
                $im = imagecreatefrombmp($file);
                break;
            case 'image/vnd.wap.wbmp':
                $im = imagecreatefromwbmp($file);
                break;
            case 'image/webp':
                $im = imagecreatefromwebp($file);
                break;
        }
    }

    private function saveIm($mime, &$newim, $f) {
        switch ($mime) {
            case 'image/png':
                imagepng($newim, $f);
                break;
            case 'image/jpeg':
                imagejpeg($newim, $f);
                break;
            case 'image/bmp':
                imagebmp($newim, $f);
                break;
            case 'image/gif':
                imagegif($newim, $f);
                break;
            case 'image/vnd.wap.wbmp':
                imagewbmp($newim, $f);
                break;
            case 'image/webp':
                imagewebp($newim, $f);
                break;
        }
    }

    private function getSizes($imw, $imh) {
        $sizes = new stdClass();
        $sizes->originalW = $imw;
        $sizes->originalH = $imh;

        $sw = $imw;
        $sh = $imh;
        if ($imw > $imh) {
            $sh = $imw;
        } else {
            $sw = $imh;
        }
        $sizes->sqW = $sw;
        $sizes->sqH = $sh;

        if ($this->width > $this->height) {
            $raport = ($this->width / $this->height);
            $twidth = $sw;
            $theight = $twidth / $raport;
        } else {
            $raport = ($this->width / $this->height);
            $theight = $sw;
            $twidth = $theight * $raport;
        }

        $sizes->resizedW = $twidth;
        $sizes->resizedH = $theight;

        $newimx = 0;
        $newimy = 0;
        $proportion = $twidth / $theight;
        $h = $sw;
        $w = $sw;
        $halign = get_option($this->option_name . '_halign');
        $valign = get_option($this->option_name . '_valign');
        if (empty($halign)) $halign = 'center';
        if (empty($valign)) $valign = 'middle';
        if ($twidth > $theight) {
            $h = $w / $proportion;
            switch ($valign) {
                case 'top':
                    $newimy = 0;
                    break;
                case 'middle':
                    $newimy = ($w - $h) / 2;
                    break;
                case 'bottom':
                    $newimy = ($w - $h);
                    break;
            }
        } else {
            $w = $w * $proportion;
            $h = $sh;
            switch ($halign) {
                case 'left':
                    $newimx = 0;
                    break;
                case 'center':
                    $newimx = ($sw - $w) / 2;
                    break;
                case 'right':
                    $newimx = ($sw - $w);
                    break;
            }
        }
        $sizes->x = $newimx;
        $sizes->y = $newimy;
        return $sizes;
    }

    private function allSizes() {
        global $_wp_additional_image_sizes;
        $sizes = $_wp_additional_image_sizes;
        $allS = get_intermediate_image_sizes();

        foreach ($allS as $t) {
            if (!isset($sizes[$t])) {
                $sizes[$t] = array(
                    'width' => get_option("{$t}_size_w"),
                    'height' => get_option("{$t}_size_h"),
                    'crop' => (bool)get_option("{$t}_size_crop"),
                );
            }
        }
        return $sizes;
    }

    public function create_square($meta) {
        if (!function_exists('imagecreatefromjpeg')) return;
        $path = $this->getPaths($meta['file']);
        $file = $this->dir . basename($meta['file']);

        if (!isset($meta['mime-type']) || empty($meta['mime-type'])) {
            $meta['mime-type'] = image_type_to_mime_type(exif_imagetype($file));
        }

        $sizes = $this->getSizes($meta['width'], $meta['height'], false);
        $newim = imagecreatetruecolor($sizes->sqW, $sizes->sqH);
        $bgcolor = $this->getColor($this->im);
        $imcolor = imagecolorallocate($newim, $bgcolor['red'], $bgcolor['green'], $bgcolor['blue']);
        imagefilledrectangle($newim, 0, 0, $sizes->sqW, $sizes->sqH, $imcolor);

        imagecopyresampled($newim, $this->im, $sizes->x, $sizes->y, 0, 0, $sizes->resizedW, $sizes->resizedH, $this->width, $this->height);
        $this->saveIm($meta['mime-type'], $newim, $file);
        imagedestroy($newim);
        return $sizes;
    }

    public function make_square_size_image($meta) {
        if (!function_exists('imagecreatefromjpeg')) return;
        if ($meta['width'] === $meta['height']) {
            return $meta;
        }

        $file = $meta['file'];
        $path = $this->getPaths($file);
        if (!isset($meta['mime-type']) || empty($meta['mime-type'])) {
            $meta['mime-type'] = image_type_to_mime_type(exif_imagetype($path->file));
        }
        $this->file = $path->file;
        $this->dir = $path->dir;
        $this->width = $meta['width'];
        $this->height = $meta['height'];
        $this->createIm($meta['mime-type'], $path->file, $this->im);

        $allsizes = $this->allSizes();
        $isallsizes = get_option($this->option_name . '_addallsizes');

        if (!empty($isallsizes)) {
            $parts = pathinfo($file);
            $name = $parts['filename'];
            $ext = $parts['extension'];
            foreach ($allsizes as $szname => $sz) {
                if (!isset($meta['sizes'][$szname])) {
                    if (empty($sz['width'])) $sz['width'] = $sz['height'];
                    if (empty($sz['height'])) $sz['height'] = $sz['width'];
                    $meta['sizes'][$szname] = array(
                        'file' => $name . '-' . $sz['width'] . 'x' . $sz['height'] . '.' . $ext,
                        'width' => $sz['width'],
                        'height' => $sz['height'],
                        'mime-type' => $meta['mime-type'],
                    );
                }
            }
        }

        foreach ($meta['sizes'] as $size => $m) {
            $result = $this->create_square($m);
            $meta[$size]['width'] = $result->sqW;
            $meta[$size]['height'] = $result->sqH;
        }

        $original = get_option($this->option_name . '_tooriginal');
        if (!empty($original)) {
            $this->create_square($meta, array(
                'width' => $meta['width'],
                'height' => $meta['height'],
            ));
            if ($meta['width'] > $meta['height']) {
                $meta['height'] = $meta['width'];
            } else {
                $meta['width'] = $meta['height'];
            }
        }
        imagedestroy($this->im);
        return $meta;
    }

    function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
        $rgbArray = array();
        if (strlen($hexStr) == 6) {
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) {
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false;
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray;
    }

    public function enqueue_styles() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/square-thumbnails-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/square-thumbnails-admin.js?v=1', array('jquery', 'wp-color-picker', 'jquery-ui-tabs'), $this->version, false);
    }

    function sqt_settings_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to perform this action.'));
        }

        if (!isset($_POST['sqt_nonce']) || !wp_verify_nonce($_POST['sqt_nonce'], 'sqt_save_settings')) {
            wp_die(esc_html__('Nonce verification failed.', 'square-thumbnails'));
        }

        update_option($this->option_name . '_halign', $_POST['halign']);
        update_option($this->option_name . '_valign', $_POST['valign']);
        update_option($this->option_name . '_bgcolor', $_POST['bgcolor']);
        update_option($this->option_name . '_getimcolor', $_POST['getimcolor']);
        update_option($this->option_name . '_dofill', $_POST['dofill']);
        update_option($this->option_name . '_tooriginal', $_POST['tooriginal']);
        update_option($this->option_name . '_addallsizes', $_POST['addallsizes']);
        wp_die();
    }

    public function square_settings() {}

    public function old_wp_version_error() {
        return;
    }
}
