<?php
/**
 * Lazy-load images gallery
 *
 * @copyright Copyright Daniel Berthereau, 2013-2016
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * Get a gallery of file thumbnails from a list of files.
 *
 * @package Omeka\View\Helper
 */
class Omeka_View_Helper_ImagesGallery extends Zend_View_Helper_Abstract
{
    /**
     * Get a gallery of file thumbnails for a list of files.
     *
     * @internal Same as item_image_gallery(), except that it uses a list of
     * files and not files attached to an item and some improvements.
     * It uses a lazy load too.
     * @see Omeka/application/libraries/globals.php item_image_gallery()
     * @see imagesToTranscribeGallery()
     *
     * @warning In some case, "jquery.lazyload.js" needs to be loaded
     * separately in the header.
     *
     * @param array $attrs HTML attributes for the components of the gallery, in
     * sub-arrays for 'wrapper', 'linkWrapper', 'link', and 'image'. Set a wrapper
     * to null to omit it.
     * @param string $imageType The type of derivative image to display.
     * @param string $filesLink Whether to link to the 'original' file or to any
     * other record url ('show'...).
     * @param array of File $files The files to use, the current files if omitted.
     * @param string $fileType The type of file, to set the title of each file.
     * @param boolean $lazyload Use lazy load or not.
     * @param boolean $addLazyloadScript If lazy load is used, the script can be
     * added or not. This is useful when this function is called multiple times
     * in one page or when the script is added in the footer.
     * @return string
     */
    public function imagesGallery(
        $attrs = array(),
        $imageType = 'square_thumbnail',
        $filesShow = 'original',
        $files = null,
        $filetype = 'image',
        $lazyload = true,
        $addLazyloadScript = true)
    {
        if (is_null($files)) {
            $files = get_current_record('files');
        }

        if (empty($files)) {
            return '';
        }
        // To avoid some errors when files are not Omeka files.
        $files = array_filter($files);

        $view = get_view();

        // Get first title of each file quickly.
        $filesTitles = $this->_getFirstTitle($files);

        $defaultAttrs = array(
            'wrapper' => array('id' => 'item-images'),
            'linkWrapper' => array(),
            'link' => array(),
            'image' => array(),
            // 'style' => 'width: auto;',
        );
        $attrs = array_merge($defaultAttrs, $attrs);

        $html = '';
        if ($attrs['wrapper'] !== null) {
            $html .= '<div ' . tag_attributes($attrs['wrapper']) . '>';
        }

        // Set the javascript and the grey image for lazy load.
        if ($lazyload) {
            queue_js_file('vendor/jquery.lazyload');
            // $grey = WEB_ROOT . '/admin/themes/default/images/bg.jpg';
            $grey = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFAAAAB4AQMAAABfIOe1AAAAA1BMVEXd3d3u346CAAAAFElEQVQ4y2NgGAWjYBSMglGADwAABSgAAXtgpQIAAAAASUVORK5CYII=';
        }

        $i = 0;
        foreach ($files as $file) {
            if ($attrs['linkWrapper'] !== null) {
                $html .= '<div ' . tag_attributes($attrs['linkWrapper']) . '>';
            }

            if ($lazyload) {
                $uri = html_escape($file->getWebPath($imageType));
                $fileTitle = isset($filesTitles[$file->id])
                    ? $filesTitles[$file->id]
                    : $file->original_filename;
                // $image = sprintf('<img class="lazy" data-original="%s" src="%s" title="%s" alt="%s" height="120" width="80" />', $uri, $grey, $fileTitle, $fileTitle);
                $image = sprintf('<img class="lazy" data-original="%s" src="%s" title="%s" alt="%s" />', $uri, $grey, $fileTitle, $fileTitle);
            }
            else {
                $image = file_image($imageType, $attrs['image'], $file);
            }
            switch ($filesShow) {
                case 'original':
                    $sizefile = $view->formatFileSize($file->size);
                    $href = $file->getWebPath('original');
                    $linkAttrs = array('href' => $href) + $attrs['link'];
                    $html .= '<a ' . tag_attributes($linkAttrs) . '>' . $image . '</a>';
                    $html .= '<p><a ' . tag_attributes($linkAttrs) . '>';
                    if ($filetype == 'image') {
                        $html .= __('Image %d', ++$i);
                    }
                    else {
                        $html .= ucfirst($file->getExtension());
                    }
                    $html .= ' [' . $sizefile . ']';
                    $html .= '</a></p>';
                    break;

                default:
                    $html .= link_to($file, $filesShow, $image, $attrs['link']);
            }

            if ($attrs['linkWrapper'] !== null) {
                $html .= '</div>';
            }
        }
        if ($attrs['wrapper'] !== null) {
            $html .= '</div>';
        }

        if ($lazyload && $addLazyloadScript) {
            $html .= '<script type="text/javascript">';
            $html .= '
jQuery(function($){
    $("img.lazy").lazyload({
        effect : "fadeIn"
    });
});';
            $html .= '</script>';
        }

        return $html;
    }

    /**
     * Get first title of a set of files.
     */
    protected function _getFirstTitle(array $records)
    {
        $db = get_db();

        $recordType = 'File';

        $recordsIds = array_map(function($record) {
            return is_object($record) ? (integer) $record->id : (integer) $record;
        }, $records);

        $commaRecordsIds = implode(', ', array_filter(array_map('intval', $recordsIds)));

        // The Dublin Core Title is always 50 in a standard install.
        $commaElementsIds = '50';
        $sqlWhereElements = "AND element_texts.element_id IN ($commaElementsIds)";
        $sqlFromElements = "FIELD(element_texts.element_id, $commaElementsIds),";

        $sql = "
        SELECT element_texts.record_id, element_texts.text
        FROM {$db->ElementText} element_texts
        WHERE element_texts.record_type = '$recordType'
            AND element_texts.record_id IN ($commaRecordsIds)
            $sqlWhereElements
        ORDER BY
            FIELD(element_texts.record_id, $commaRecordsIds),
            $sqlFromElements
            element_texts.id ASC;
        ";
        $result = $db->fetchPairs($sql);

        return $result;
    }
}
