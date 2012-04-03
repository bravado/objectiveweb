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

// thinking about the future...
// defined('ATTACHMENT_HANDLER') or define('ATTACHMENT_HANDLER', 'FSAttachmentHandler');

// Filesystem paths
defined('OW_CONTENT') or define('OW_CONTENT', ROOT . '/ow-content');

// Constants
define('ATTACHMENT_UNLINK', 1);
define('ATTACHMENT_OVERWRITE', 2);

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
    $directory = sprintf("%s/%s/%s", OW_CONTENT, $domain, $id);

    if (!is_dir($directory)) {
        if (file_exists($directory)) {
            throw new Exception("Cannot write to $directory", 500);
        }

        mkdirs($directory);
    }

    if($attachment) {
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
    $dir = opendir(attachment_filename($domain, $id));

    $files = array();
    while (($file = readdir($dir)) != null) {


        if (!is_dir($file)) {
            $files[] = attachment_meta($domain, $id, $file);

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
    return array(
        'url' => "/$domain/$id/$attachment",
        'size' => filesize($filename)
    );
}


function attachment_open($domain, $id, $attachment) {
    $filename = attachment_filename($domain, $id, $attachment);

    if(!file_exists($filename)) {
        throw new Exception(sprintf("Attachment %s does not exist", $filename), 404);
    }

    return fopen($filename, "rb");
}
/**
 * The default FSAttachmentHandler stores files on the OW_CONTENT directory
 * Those files could also be served directly through the OW_CONTENT_URL address
 */
class FSAttachmentHandler extends OWHandler {

    /**
     * Delete an attachment
     * @param $file
     */
    function delete($file) {

    }

    /**
     * Lists all attachments for this url
     */
    function fetch() {

    }

    /**
     * Retrieves an attachment from disk
     * @param $file
     */
    function get($file, $params) {
        // TODO maybe could return just a pointer to a file
    }

    /**
     * Attaches files to the url
     * @param null $files
     * @return string|The|void
     */
    function post($files) {

    }

    /**
     * Adds/updates an attachment directly
     * @param $file
     * @param Array $data
     * @return array|void
     */
    function put($file, $data) {

    }

}