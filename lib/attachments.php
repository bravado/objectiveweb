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
include_once dirname(__FILE__).'/elFinder/elFinderConnector.class.php';
include_once dirname(__FILE__).'/elFinder/elFinder.class.php';
include_once dirname(__FILE__).'/elFinder/elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).'/elFinder/elFinderVolumeLocalFileSystem.class.php';

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


function attachments($domain, $id) {
    $opts = array(
        // 'debug' => true, TODO set debug
        'roots' => array(
            array(
                'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
                'path'          => attachment_filename($domain, $id),         // path to files (REQUIRED)
                'URL'           => OW_URL."/$domain/$id" // URL to files (REQUIRED)
                //,'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
            )
        )
    );

    return new elFinderConnector(new elFinder($opts));
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
        while (!feof($content)) {
            fwrite($fp, fread($content, 8192));
        }
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
 * @return string
 * @throws Exception If the attachment's parent already exists and is not a directory
 */
function attachment_filename($domain, $id, $attachment = null) {

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
    $dir = @opendir(attachment_filename($domain, $id));

    $files = array();

    if($dir !== FALSE) {
        while (($file = readdir($dir)) != null) {


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

