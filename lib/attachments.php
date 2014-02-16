<?php
/**
 * Objectiveweb
 *
 * Default attachment handler
 *
 * User: guigouz
 * Date: 03/04/12
 * Time: 10:14
 */
include_once dirname(__FILE__).'/elfinder/elFinderConnector.class.php';
include_once dirname(__FILE__).'/elfinder/elFinder.class.php';
include_once dirname(__FILE__).'/elfinder/elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).'/elfinder/elFinderVolumeLocalFileSystem.class.php';

// thinking about the future...
// defined('ATTACHMENT_HANDLER') or define('ATTACHMENT_HANDLER', 'FSAttachmentHandler');

// Filesystem paths
defined('OW_CONTENT') or define('OW_CONTENT', ROOT . '/ow-content');

/**
 * ATTACHMENT_HASHDEPTH enables hashed directoried for attachment storage
 * Recommended for performance and to avoid filesystem limitations
 * Set to 0 to disable
 */
defined('ATTACHMENT_HASHDEPTH') or define('ATTACHMENT_HASHDEPTH', 2);

// Constants
define('ATTACHMENT_UNLINK', 1);
define('ATTACHMENT_OVERWRITE', 2);


class Attachments extends OWService {

    // Service ID
    var $id = 'attachments';

    function get($id, $data) {

        if($id) {
            $data['_attachments'] = attachment_list($this->domain, $id);
        }

        return $data;
    }

    function service($id) {

        $connector = new elFinderConnector(_attachments($this->domain, $id));
        $connector->run();
    }

}

/**
 * Returns an AttachmentManager to interact with the elFinder API
 * @param $domain
 * @param $id
 * @return OWAttachmentManager
 */
function _attachments($domain, $id) {
    $opts = array(
        // 'debug' => true, TODO set debug
        'roots' => array(
            array(
                'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
                'path'          => attachment_filename($domain, $id),         // path to files (REQUIRED)
                'URL'           => OW_URL."index.php/$domain/$id", // URL to files (REQUIRED)
                'accessControl' => 'hide_dot_files'             // disable and hide dot starting files (OPTIONAL)
            )
        )
    );

    return new OWAttachmentManager($opts);
}



/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function hide_dot_files($attr, $path, $data, $volume) {
    return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
        ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
        :  null;                                    // else elFinder decide it itself
}


/**
 * Downloads an attachment
 *
 * @param $domain String Domain
 * @param $id String Resource ID
 * @param $filename String Attachment Filename
 */
function attachment_get($domain, $id, $filename) {
    $attachments = _attachments($domain, $id);

    $attachments->download($filename);
}


/**
 * Creates/updates an attachment
 * @param $oid
 * @param Array|string $data describing the attachment or filename
 *  array("name" => "file_name.ext", "type" => "mime/type", "data" => "file_data")
 *  "file_name.ext"
 * @param int $params ORed constants ATTACHMENT_UNLINK, ATTACHMENT_OVERWRITE
 * @return Array The new attachment metadata
 */
function attach($domain, $id, $attachment, $content = null, $params = 0) {
    $filename = attachment_filename($domain, $id, $attachment);

    if(file_exists($filename)) {
        if(!($params & ATTACHMENT_OVERWRITE)) {
            throw new Exception('Attachment already exists', 409);
        }
    }

    // TODO save metadata to database (/attachments domain?)
    if (is_resource($content)) {
        $fp = fopen($filename, "wb");

        stream_copy_to_stream($content, $fp);

        fclose($fp);
    }
    else {
        file_put_contents($filename, $content);
    }

    return attachment_meta($domain, $id, $attachment);
}

/**
 * Attaches a local file to the resource
 *
 * @param $domain
 * @param $id
 * @param $attachment Array|string describing the attachment of filename
 * @param $local_filename string - full path to a filename on the local server
 * @param int $params ORed constants ATTACHMENT_UNLINK, ATTACHMENT_OVERWRITE
 *
 * @see attachment_filename($domain, $id, $attachment)
 */
function attach_local($domain, $id, $attachment, $local_filename, $params = 0) {
    $filename = attachment_filename($domain, $id, $attachment);

    if(file_exists($filename)) {
        if(!($params & ATTACHMENT_OVERWRITE)) {
            throw new Exception('Attachment already exists', 409);
        }
    }

    copy($local_filename, $filename);

    if ($params & ATTACHMENT_UNLINK) {
        unlink($local_filename);
    }

    return attachment_meta($domain, $id, $attachment);

}

function attachment_delete($domain, $id, $attachment) {
    $filename = attachment_filename($domain, $id, $attachment);

    if(!file_exists($filename)) {
        throw new Exception('Attachment does not exist', 404);
    }
    else {
        unlink($filename);
    }
}
/**
 *
 * @param $domain
 * @param $id
 * @param $attachment Array|string|null $data describing the attachment or filename. If null returns the attachment directory
 *  array("name" => "file_name.ext", "type" => "mime/type", "data" => "file_data")
 *  "file_name.ext"
 * @param $create Create a local directory to store the attachments
 * @return string
 * @throws Exception If the attachment's parent already exists and is not a directory
 */
function attachment_filename($domain, $id, $attachment = null, $create = true) {

    $hashed = '';
    if(is_array($id)) {
        $id = md5(implode("", $id));
        for($i = 0; $i < ATTACHMENT_HASHDEPTH; $i++) {
            $hashed = $hashed."/{$id[$i]}";
        }
    }
    else if (is_numeric($id)) {
        $idhash = strrev(substr($id, 0, ATTACHMENT_HASHDEPTH));

        for($i = 0; $i < ATTACHMENT_HASHDEPTH; $i++) {
            $hashed = '/'.(isset($idhash[$i]) ? $idhash[$i] : '0').$hashed;
        }
    }
    else {
        for($i = 0; $i < ATTACHMENT_HASHDEPTH; $i++) {
            $hashed = $hashed."/{$id[$i]}";
        }
    }

    $directory = sprintf("%s/%s%s/%s", OW_CONTENT, $domain, $hashed, $id);


    if($attachment) {
        if (!is_dir($directory)) {
            if (file_exists($directory)) {
                throw new Exception("Cannot write to $directory", 500);
            }

            mkdirs($directory);
        }

        return sprintf("%s/%s", $directory, is_array($attachment) ? $attachment['name'] : $attachment);
    }
    else {
        return $directory;
    }
}

/**
 * Lists all attachments for a particular domain/id
 * @param $domain
 * @param $id
 */
function attachment_list($domain, $id) {
    $dir = @opendir(attachment_filename($domain, $id, null, false));

    $files = array();

    if($dir !== FALSE) {
        while (($file = readdir($dir)) != null) {

            // TODO list directories also ?
            if (!is_dir($file)) {
                $files[] = attachment_meta($domain, $id, $file);

            }
        }
    }

    return $files;
}

function attachment_meta($domain, $id, $attachment) {
    $filename = attachment_filename($domain, $id, $attachment);
    // TODO add other info (depending on file type)
//    if (substr($file, -4) == 'html') {
//        $contents = file_get_contents($file);
//        if (preg_match('/<title>([^<]+)/', $contents, $matches)) {
//            $file_meta['title'] = $matches[1];
//        }
//    }

    $mime = "application/octet-stream";
    $extension = strtolower(substr($filename, strrpos($filename, ".") + 1));

    switch($extension) {
        case 'jpeg':
        case 'jpg':
        case 'gif':
        case 'png':
            $mime = "image/$extension";
            break;
    }

    return array(
        'seq' => rand(),
        'name' => $attachment,
        'url' => "/$domain/$id/$attachment",
        'size' => filesize($filename),
        'mime' => $mime
    );
}


class OWAttachmentManager extends elFinder {
    public function __construct($opts) {
        parent::__construct($opts);
    }

    public function download($filename) {

        // Get the first volume
        // http://stackoverflow.com/questions/1028668/get-first-key-in-a-possibly-associative-array
        $volume = reset($this->volumes);

        $hash = strtr(base64_encode($filename), '+/=', '-_.');
        // remove dots '.' at the end, before it was '=' in base64
        $hash = rtrim($hash, '.');
        // append volume id to make hash unique
        $target = $volume->id().$hash;

        $file = $this->file(array('target' => $target));

        if(!$file) {
            throw new Exception(_('Attachment not found'), 404);
        }

        foreach($file['header'] as $header){
            header($header);
        }

        fpassthru($file['pointer']);
        fclose($file['pointer']);
        exit;
    }


}