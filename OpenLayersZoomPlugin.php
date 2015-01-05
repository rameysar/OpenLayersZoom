<?php
/**
 * OpenLayers Zoom: an OpenLayers based image zoom widget.
 *
 * @copyright Daniel Berthereau, 2013-2015
 * @copyright Peter Binkley, 2012-2013
 * @copyright Matt Miller, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The OpenLayers Zoom plugin.
 *
 * @package Omeka\Plugins\OpenLayersZoom
 */
class OpenLayersZoomPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'admin_items_batch_edit_form',
        'items_batch_edit_custom',
        'public_head',
        'after_save_item',
        'before_delete_file',
        'open_layers_zoom_display_file',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'admin_items_form_tabs',
        // Currently, it's a checkbox, so no error can be done.
        // 'items_batch_edit_error',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'openlayerszoom_tiles_dir' => '/zoom_tiles',
        'openlayerszoom_tiles_web' => '/zoom_tiles',
        'openlayerszoom_use_public_head' => true,
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['openlayerszoom_tiles_dir'] = FILES_DIR . DIRECTORY_SEPARATOR . 'zoom_tiles';
        // define('ZOOMTILES_WEB', 'http://ec2-75-101-192-109.compute-1.amazonaws.com/cgi-bin/iipsrv.fcgi?zoomify=/var/www/jp2samples');
        $this->_options['openlayerszoom_tiles_web'] = WEB_FILES . '/zoom_tiles';

        $this->_installOptions();

        // Check if there is a directory in the archive for the zoom titles we
        // will be making.
        $tilesDir = get_option('openlayerszoom_tiles_dir');
        if (!file_exists($tilesDir)) {
            mkdir($tilesDir);
            @chmod($tilesDir, 0755);

            copy(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . 'index.html', $tilesDir . DIRECTORY_SEPARATOR . 'index.html');
            @chmod($tilesDir . DIRECTORY_SEPARATOR . 'index.html', 0644);
        }
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        // Nuke the zoom tiles directory.
        $tilesDir = get_option('openlayerszoom_tiles_dir');
        $this->_rrmdir($tilesDir);

        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial('plugins/openlayerszoom-config-form.php');
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        $post['openlayerszoom_tiles_dir'] = realpath(trim($post['openlayerszoom_tiles_dir']));
        foreach ($post as $key => $value) {
            set_option($key, $value);
        }
    }

    /**
     * Add a partial batch edit form.
     *
     * @return void
     */
    public function hookAdminItemsBatchEditForm($args)
    {
        $view = $args['view'];
        echo get_view()->partial(
            'forms/openlayerszoom-batch-edit.php'
        );
    }

    /**
     * Process the partial batch edit form.
     *
     * @return void
     */
    public function hookItemsBatchEditCustom($args)
    {
        $item = $args['item'];
        $zoomify = $args['custom']['openlayerszoom']['zoomify'];

        if (!$zoomify) {
            return;
        }

        $supportedFormats = array(
            'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
            'jpg' => 'Joint Photographic Experts Group JFIF format',
            'png' => 'Portable Network Graphics',
            'gif' => 'Graphics Interchange Format',
            'tif' => 'Tagged Image File Format',
            'tiff' => 'Tagged Image File Format',
        );
        // Set the regular expression to match selected/supported formats.
        $supportedFormatRegEx = '/\.' . implode('|', array_keys($supportedFormats)) . '$/i';

        // Retrieve image files from the item.
        $view = get_view();
        $creator = new OpenLayersZoom_Creator();
        foreach($item->Files as $file) {
            if ($file->hasThumbnail()
                    && preg_match($supportedFormatRegEx, $file->filename)
                    && !$view->openLayersZoom()->isZoomed($file)
                ) {
                $creator->createTiles($file->filename);
            }
        }
    }

    /**
     * Add css and js in the header of the public theme.
     */
    public function hookPublicHead($args)
    {
        if (!get_option('openlayerszoom_use_public_head')) {
            return;
        }

        $view = $args['view'];

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getControllerName() == 'items'
                && $request->getActionName() == 'show'
                && $view->openLayersZoom()->zoomedFilesCount($view->item) > 0
            ) {
            queue_css_file('OpenLayersZoom');
            queue_js_file(array(
                'OpenLayers',
                'OpenLayersZoom',
            ));
        }
    }

    /**
     * Fired once the record is saved, if there is a `open_layers_zoom_filename`
     * passed in the $_POST along with save then we know that we need to zoom
     * resource.
     */
    public function hookAfterSaveItem($args)
    {
        if (!$args['post']) {
            return;
        }

        $item = $args['record'];
        $post = $args['post'];

        // Loop through and see if there are any files to zoom.
        // Only checked values are posted.
        $filesave = false;
        $view = get_view();
        $creator = new OpenLayersZoom_Creator();
        $files = $creator->getFilesById($item);
        foreach ($post as $key => $value) {
            // Key is the file id of the stored image, value is the filename.
            if (strpos($key, 'open_layers_zoom_filename_') !== false) {
                $file = $files[(int) substr($key, strlen('open_layers_zoom_filename_'))];
                if (!$view->openLayersZoom()->isZoomed($file)) {
                    $creator->createTiles($value);
                }
                $filesaved = true;
            }
            elseif ((strpos($key, 'open_layers_zoom_removed_hidden_') !== false) && ($filesaved != true)) {
                $creator->removeZDataDir($value);
            }
        }
    }

    /**
     * Manages deletion of the folder of a file when this file is removed.
     */
    public function hookBeforeDeleteFile($args)
    {
        $file = $args['record'];
        $item = $file->getItem();

        $creator = new OpenLayersZoom_Creator();
        $creator->removeZDataDir($file);
    }

    /**
     * Controls how the image will be returned.
     *
     * @todo Need to change this based on how non-zoomed images are to be
     * presented.
     *
     * @param array $args
     *   Array containing:
     *   - 'file': object a file object
     *   - 'options'
     *
     * @return string
     */
    public function hookOpenLayersZoomDisplayFile($args = array())
    {
        if (!isset($args['file'])) {
            return '';
        }

        $file = $args['file'];
        $options = isset($args['options']) ? $args['options'] : array();

        // Is it a zoomified file?
        $view = get_view();
        $tileUrl = $view->openLayersZoom()->getTileUrl($file);

        // Do not show the zoomer on the admin page.
        if ($tileUrl) {
            // Root is not used in the javascript, but only here.
            $creator = new OpenLayersZoom_Creator();
            list($root, $ext) = $creator->getRootAndExtension($file->filename);

            // Grab the width/height of the original image.
            list($width, $height, $type, $attr) = getimagesize(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename);

            // If the var is set then they are requesting a specific image to be
            // zoomed not just the first.
            // This is kind of a hack to get around some problems with OpenLayers
            // displaying multiple zoomify layers on a single page.
            // It doesn't even come into play if there is just one zoomed image
            // per record.
            $open_zoom_layer_req = isset($_REQUEST['open_zoom_layer_req'])
                ? html_escape($_REQUEST['open_zoom_layer_req'])
                : '-1';

            $html = '<script type="text/javascript">
                open_layers_zoom_add_zoom("' . $root . '","' . $width . '","' . $height . '","' . $tileUrl . '/",' . $open_zoom_layer_req . ');
            </script>';
        }

        // Else display normal file.
        else {
            $html = file_markup($file, $options);
        }
        echo $html;
    }

    /**
     * Adds the zoom options to the images attached to the record, it inserts a
     * "Zoom" tab in the admin->edit page
     *
     * @return array of tabs
     */
    public function filterAdminItemsFormTabs($tabs, $args)
    {
        $item = $args['item'];

        $useHtml = '<span>' . __('Only images files attached to the record can be zoomed.') . '</span>';
        $zoomList = '';

        $view = get_view();
        foreach($item->Files as $file) {
            if (strpos($file->mime_type, 'image/') === 0) {
                // See if this image has been zoooomed yet.
                if ($view->openLayersZoom()->isZoomed($file)) {
                    $isChecked = '<input type="checkbox" checked="checked" name="open_layers_zoom_filename_' . $file->id . '" id="open_layers_zoom_filename_' . $file->id . '" value="' . $file->filename . '"/>' . __('This image is zoomed.') . '</label>';
                    $isChecked .= '<input type="hidden" name="open_layers_zoom_removed_hidden_' . $file->id . '" id="open_layers_zoom_removed_hidden_' . $file->id . '" value="' . $file->filename . '"/>';

                    $title = __('Click and Save Changes to make this image un zoom-able');
                    $style_color = "color:green";
                }
                else {
                    $isChecked = '<input type="checkbox" name="open_layers_zoom_filename_' . $file->id . '" id="open_layers_zoom_filename_' . $file->id . '" value="' . $file->filename . '"/>' . __('Zoom this image') . '</label>';
                    $title = __('Click and Save Changes to make this image zoom-able');
                    $style_color = "color:black";
                }

                $useHtml .= '
                <div style="float:left; margin:10px;">
                    <label title="' . $title . '" style="width:auto;' . $style_color . ';" for="zoomThis_' . $file->id . '">'
                    . file_markup($file, array('imageSize'=>'thumbnail'))
                    . $isChecked . '<br />
                </div>';
            }
        }

        $ttabs = array();
        foreach($tabs as $key => $html) {
            if ($key == 'Tags') {
                $ttabs['Zoom'] = $useHtml;
            }
            $ttabs[$key] = $html;
        }
        $tabs = $ttabs;
        return $tabs;
    }

    /**
     * Removes directories recursively.
     *
     * @param string $dirPath Directory name.
     *
     * @return boolean
     */
    protected function _rrmdir($dirPath)
    {
        $glob = glob($dirPath);
        foreach ($glob as $g) {
            if (!is_dir($g)) {
                unlink($g);
            }
            else {
                $this->_rrmdir("$g/*");
                rmdir($g);
            }
        }
        return true;
    }
}
