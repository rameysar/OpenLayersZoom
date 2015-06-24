<?php
/**
 * Helpers for OpenLayersZoom.
 *
 * @package OpenLayersZoom
 */
class OpenLayersZoom_View_Helper_OpenLayersZoom extends Zend_View_Helper_Abstract
{
    /**
     * The creator is used to check if a zoom exists.
     */
    protected $_creator;

    /**
     * Load the OpenLayersZoom Creator one time only.
     */
    public function __construct()
    {
        $this->_creator = new OpenLayersZoom_Creator();
    }

    /**
     * Get the helper.
     *
     * @return This view helper.
     */
    public function openLayersZoom()
    {
        return $this;
    }

    /**
     * Returns an OpenLayersZoom to display for an item or a file.
     *
     * @param Record $record Item or File to zoom.
     *
     * @return html.
     */
    public function zoom($record)
    {
        $html = '';
        switch (get_class($record)) {
            case 'Item':
				$zoomedFiles = $this->getZoomedFiles($record);
                if (!empty($zoomedFiles)) {                  
                   foreach ($zoomedFiles as $file) {
                        $html = $this->_zoomFile($file);
                   }   
                }
                break;

            case 'File':
                $result = $this->_zoomFile($record);
                if ($result) {
                        $html = '<div class="openlayerszoom-images">';
						$html .= $this->_zoomFile($file);
						$html .= '</div>';
                }
                break;
        }

        return $html;
    }

    /**
     * Get an array of all zoomed images of an item.
     *
     * @param object $item
     *
     * @return array
     *   Associative array of file id and files.
     */
    public function getZoomedFiles($item = null)
    {
        if ($item == null) {
            $item = get_current_record('item');
        }

        $list = array();
        foreach($item->Files as $file) {
            if ($this->isZoomed($file)) {
                $list[$file->id] = $file;
            }
        }
        return $list;
    }

    /**
     * Count the number of zoomed images attached to an item.
     *
     * @param object $item
     *
     * @return integer
     *   Number of zoomed images attached to an item.
     */
    public function zoomedFilesCount($item = null)
    {
        return count($this->getZoomedFiles($item));
    }

    /**
     * Determine if a file is zoomed.
     *
     * @param object $file
     *
     * @return boolean
     */
    public function isZoomed($file = null)
    {
        return (boolean) $this->getTileUrl($file);
    }

    /**
     * Get the url to tiles or a zoomified file, if any.
     *
     * @param object $file
     *
     * @return string
     */
    public function getTileUrl($file = null)
    {
        if ($file == null) {
            $file = get_current_record('file');
        }
        if (empty($file)) {
            return;
        }

        $tileUrl = '';
        // Does it use a IIPImage server?
        if ($this->_creator->useIIPImageServer()) {
            $item = $file->getItem();
            $tileUrl = $item->getElementTexts('Item Type Metadata', 'Tile Server URL');
            $tileUrl = empty($tileUrl) ? '' : $tileUrl[0]->text;
        }

        // Does it have zoom tiles?
        elseif (file_exists($this->_creator->getZDataDir($file))) {
            // fetch identifier, to use in link to tiles for this jp2 - pbinkley
            // $jp2 = item('Dublin Core', 'Identifier') . '.jp2';
            // $tileUrl = ZOOMTILES_WEB . '/' . $jp2;
            $tileUrl = $this->_creator->getZDataWeb($file);
    }

        return $tileUrl;
    }

    /**
     * Helper to zoom a file.
     */
		/*Adds to string: "<div class="openlayerszoom-images, id="some id">, _zoomFile($file), </div>" */
		
		/*String becomes: "<div class="openlayerszoom-images, id="some id">, <script type="text/javascript">, 
		* open_layers_zoom_add_zoom("' . $root . '","' . $width . '","' . $height . '","' . $tileUrl . '/",' . $open_zoom_layer_req . '), 
		* </script>, </div>" 
		*$root doesn't matter; $req doesn't matter; Only $width, $height and $tileUrl do anything.
		*calls to javascript for the function open_layers_zoom_add_zoom
		*/

    protected function _zoomFile($file)
    {
		/* $file becomes a retrieved $tileUrl*/
        $tileUrl = $this->getTileUrl($file);
		/* If there is a tileUrl that has been successfully retrieved... */
        if ($tileUrl) {
            // Root is not used in the javascript, but only here.
			// Grabs the filename of each $file to create the $root
            list($root, $ext) = $this->_creator->getRootAndExtension($file->filename);

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
			
            $html = '<script type="text/javascript">'
                . 'open_layers_zoom_add_zoom("' . $root . '","' . $width . '","' . $height . '","' . $tileUrl . '/",' . $open_zoom_layer_req . ');'
            . '</script>';

            return $html;
        }
    }
}
