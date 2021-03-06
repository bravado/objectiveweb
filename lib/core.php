<?php
/**
 * ObjectiveWeb
 *
 * Platform Core
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:09
 */

define('OBJECTIVEWEB_VERSION', '0.6');

// Global system variables
$_domains = array();
$_apps = array();

// TODO register dynamic domains (on the directory)

function ow_version() {
    // TODO incluir informações de plugin se DEBUG
    global $_apps, $_domains;

    if (DEBUG) {
        return sprintf('{ "objectiveweb": "%s", "apps" : %s, "domains": %s }', OBJECTIVEWEB_VERSION, json_encode($_apps), json_encode($_domains));
    } else {
        return sprintf('{ "objectiveweb": "%s" }', OBJECTIVEWEB_VERSION);
    }
}

function delete($domain, $id) {
    /** @var $handler OWHandler */
    $handler = get($domain);

    $handler->apply_filters('delete', $id);

    if (isset($handler->params['delete'])) {
        return call_user_func_array($handler->params['delete'], array($handler, $id));
    } else {
        return $handler->delete($id);
    }
}

function fetch($domain, $params = array()) {

    preg_match('/(_?[a-z]+)\/?_?([a-z]*)\/?(.*)/', $domain, $m);

    // Handle views (/domain/_view)
    $handler = get($m[1]);

    if ($m[2]) {
        $view_handler = @$handler->params['views'][$m[2]];

        // if the view is an array, perform this query
        if (is_array($view_handler)) {
            $params = array_merge($params, $view_handler);
        } elseif (is_callable($view_handler)) {
            $params = call_user_func_array($view_handler, array($m[3], $params));
        } else {
            throw new Exception(_('View not found'), 404);
        }
    }

    if (isset($handler->params['fetch'])) {
        $result = call_user_func_array($handler->params['fetch'], array($handler, $params));
    } else {
        $result = $handler->fetch($params);
    }


    return $handler->apply_filters('get', null, $result);
}


/**
 * @throws Exception
 * @param $domain_id
 * @param null $id
 * @return OWHandler
 */
function get($domain_id, $id = null, $params = array()) {
    global $_domains;

    if (empty($_domains[$domain_id])) {
        throw new Exception(_('Domain not found'), 404);
    }

    if (empty($_domains[$domain_id]['instance'])) {

        if (!class_exists($_domains[$domain_id]['handler'])) {
            throw new Exception(sprintf(_('Invalid handler %s'), $_domains[$domain_id]['handler']), 500);
        }


        $instance = new $_domains[$domain_id]['handler']($domain_id, $_domains[$domain_id]);

        $_domains[$domain_id]['instance'] = $instance;
    }

    /** @var $handler OWHandler */
    $handler = $_domains[$domain_id]['instance'];

    $params = $handler->apply_filters('fetch', $id, $params);

    if ($id) {

        if (isset($handler->params['get'])) {
            $rsrc = call_user_func_array($handler->params['get'], array($handler, $id, $params));
        } else {
            $rsrc = $handler->get($id, $params);
        }

        // Filter result
        if ($rsrc) {
            $rsrc = $handler->apply_filters('get', $id, $rsrc);
        }
        else {
            throw new Exception(sprintf(_('%s %s not found'), $domain_id, $id), 404);
        }

        return $rsrc;
    } else {
        return $handler;
    }


}

function options($domain) {
    $handler = get($domain);

    return $handler->options();
}

function post($domain, $data) {

    /** @var $handler OWHandler */
    $handler = get($domain);

    $data = $handler->apply_filters('post', null, $data);

    if (isset($handler->params['post'])) {
        return call_user_func_array($handler->params['post'], array($handler, $data));
    } else {
        return $handler->post($data);
    }

}

function put($domain, $id, $data) {

    $handler = get($domain);

    $data = $handler->apply_filters('put', $id, $data);

    if (isset($handler->params['put'])) {
        return call_user_func_array($handler->params['put'], array($handler, $id, $data));
    } else {
        return $handler->put($id, $data);
    }
}

// Bootstrap/initialization functions (register_*)

/**
 * @param $id - The application ID
 * @param string $root - ROOT directory when looking for apps (defaults to web root)
 * @throws Exception
 */
function register_app($id, $root = ROOT) {
    global $_apps;
    $_init = "$root/$id/_init.php";
    if (!isset($_apps[$id])) {
        if (is_readable($_init)) {
            $_apps[$id] = $_init;
        } else {
            throw new Exception(sprintf(_('Impossible to register %s: %s not found'), $id, $_init));
        }
    }
}

function register_domain($domain_id, $params = array()) {
    global $_domains;

    if (isset($_domains[$domain_id])) {
        throw new Exception(sprintf(_('Domain %s already registered'), $domain_id));
    }

    if (!is_array($params)) {
        $params = json_decode($params, true);

        if ($params === null) {
            throw new Exception(_('Invalid domain parameters'));
        }
    }

    $defaults = array(
        'handler' => 'ObjectHandler'
    );

    // TODO validate schema


    $_domains[$domain_id] = array_merge($defaults, $params);
}

/**
 * List the registered domains
 *
 * @return array
 */
function ow_domains() {
    global $_domains;

    return array_keys($_domains);
}


/**
 * @brief Generates a Universally Unique IDentifier, version 4.
 *
 * @see http://tools.ietf.org/html/rfc4122#section-4.4
 * @see http://en.wikipedia.org/wiki/UUID
 * @return string A UUID, made up of 32 hex digits and 4 hyphens.
 */
function ow_oid() {

    $pr_bits = null;
    $fp = @fopen('/dev/urandom', 'rb');
    if ($fp !== false) {
        $pr_bits .= @fread($fp, 16);
        @fclose($fp);
    } else {
        // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
        $pr_bits = "";
        for ($cnt = 0; $cnt < 16; $cnt++) {
            $pr_bits .= chr(mt_rand(0, 255));
        }
    }

    $time_low = bin2hex(substr($pr_bits, 0, 4));
    $time_mid = bin2hex(substr($pr_bits, 4, 2));
    $time_hi_and_version = bin2hex(substr($pr_bits, 6, 2));
    $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits, 8, 2));
    $node = bin2hex(substr($pr_bits, 10, 6));

    /**
     * Set the four most significant bits (bits 12 through 15) of the
     * time_hi_and_version field to the 4-bit version number from
     * Section 4.1.3.
     * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
     */
    $time_hi_and_version = hexdec($time_hi_and_version);
    $time_hi_and_version = $time_hi_and_version >> 4;
    $time_hi_and_version = $time_hi_and_version | 0x4000;

    /**
     * Set the two most significant bits (bits 6 and 7) of the
     * clock_seq_hi_and_reserved to zero and one, respectively.
     */
    $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

    return sprintf('%08s-%04s-%04x-%04x-%012s',
        $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
}

// publish/subscribe (may become realtime)

function ow_subscribe($domain, $event, $callback) {

    global $_subscriptions;

    // TODO verificar se callback já está in_array ?

    if (empty($_subscriptions["$domain:$event"])) {
        $_subscriptions["$domain:$event"] = array($callback);
    } else {
        $_subscriptions["$domain:$event"][] = $callback;
    }

}

function ow_trigger($domain, $event, $data) {
    global $_subscriptions;

    foreach (@$_subscriptions["$domain:$event"] as $callback) {
        call_user_func($_subscriptions["$domain:$event"], $data);
    }

}

/**
 * from http://www.php.net/manual/en/function.uniqid.php#94959
 */
class UUID {
    public static function v3($namespace, $name) {
        if (!self::is_valid($namespace)) return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',

// 32 bits for "time_low"
            substr($hash, 0, 8),

// 16 bits for "time_mid"
            substr($hash, 8, 4),

// 16 bits for "time_hi_and_version",
// four most significant bits holds version number 3
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,

// 16 bits, 8 bits for "clk_seq_hi_res",
// 8 bits for "clk_seq_low",
// two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

// 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

    public static function v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

// 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

// 16 bits for "time_mid"
            mt_rand(0, 0xffff),

// 16 bits for "time_hi_and_version",
// four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

// 16 bits, 8 bits for "clk_seq_hi_res",
// 8 bits for "clk_seq_low",
// two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

// 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function v5($namespace, $name) {
        if (!self::is_valid($namespace)) return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',


            substr($hash, 0, 8), // 32 bits for "time_low"


            substr($hash, 8, 4), // 16 bits for "time_mid"

// 16 bits for "time_hi_and_version",
// four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

// 16 bits, 8 bits for "clk_seq_hi_res",
// 8 bits for "clk_seq_low",
// two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

// 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

    public static function is_valid($uuid) {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
        '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }
}


class OWHandler {

    var $id;
    var $with = array();
    var $params = array();

    function OWHandler($id, $params = array()) {
        $this->id = $id;

        $this->params = array_merge(array('with' => array('acl', 'attachments')), $params);

        foreach ($this->params['with'] as $service) {
            $this->$service = new $service($this);
        }


        $this->init();
    }

    function apply_filters($method, $id, $data = null) {


        foreach ($this->params['with'] as $filter) {

            if (is_callable(array($this->$filter, $method))) {
                $data = $this->$filter->$method($id, $data);
            }

        }

        return $data;
    }

    function init() {

    }

    /**
     * Retrieves an object from a domain given its OID
     *
     * @param  $domain
     * @param  $oid
     * @param array $params
     * @return void
     */
    function get($oid) {
        throw new Exception('Not implemented', 500);
    }


    function fetch($cond = array()) {
        throw new Exception('Not implemented', 500);
    }


    function options() {
        throw new Exception('Not implemented', 500);
    }

    /**
     * Creates a new Object on a domain
     *
     * @param null $data The object data
     * @return string|The object's new oid.
     */
    function post($data = null) {
        throw new Exception('Not implemented', 500);
    }

    /**
     * Writes data to an existing object
     * @param  $oid
     * @param Array $data
     * @return array The changed data
     */
    function put($oid, $data) {
        throw new Exception('Not implemented', 500);
    }


    function delete($oid) {
        throw new Exception('Not implemented', 500);
    }
}

class OWService {
    var $id = null;
    var $domain;
    var $handler;

    function __construct($handler) {
        $this->handler = $handler;
        $this->domain = $handler->id;
    }

    /**
     * Called before deleting a resource
     * @param $id String the resource id
     */
    function delete($id) {

    }

    /**
     * Called before fetching a resource or domain (id may be null)
     * @param $id
     */
    function fetch($id = null) {

    }

    /**
     * Called after fetching a resource
     * @param $id Resource id
     * @param $data Resource content
     * @return mixed Modified resource content
     */
    function get($id, $data) {
        return $data;
    }

    /**
     * Called before creating a new resource
     * @param $data Array with the new resource contents
     * @return Array Modified data which will be persisted
     */
    function post($id, $data) {

        return $data;
    }


    /**
     * Called before modifying a resource
     * @param $id String The resource ID
     * @param $data Array with new contents
     * @return mixed Modified data which will be persisted
     */
    function put($id, $data) {
        return $data;
    }


    /**
     * Handle direct requests to this service (/domain/id/_service)
     * @param $domain
     * @param $id
     * @throws Exception
     */
    function service($id) {
        throw new Exception('Not implemented', 500);
    }
}

/**
 * The FileStore lists a directory with files with an optional filter
 * This also allows reading/writing to arbitrary files
 *
 * Note: This handler does not support subdirectories
 */
class FileStore extends OWHandler {

    var $root;

    function init() {
        if (!is_dir($this->params['root'])) {
            throw new Exception('Invalid Directory Root ' . $this->params['root']);

        }

        $this->root = $this->params['root'];
    }

    function get_metadata($file) {
        $file = "$this->root/$file";

        $file_meta = array(
            "name" => basename($file),
            "size" => filesize($file),
            "md5" => md5(file_get_contents($file))
        );

        if (substr($file, -4) == 'html') {
            $contents = file_get_contents($file);
            if (preg_match('/<title>([^<]+)/', $contents, $matches)) {
                $file_meta['title'] = $matches[1];
            }
        }

        return $file_meta;
    }

    function fetch($params = array()) {
        $dir = opendir($this->root);

        $files = array();
        while (($file = readdir($dir)) != null) {


            if (!is_dir($file)) {
                $files[] = $this->get_metadata($file);

            }
        }

        return $files;
    }

    function get($file) {
        return file_get_contents($this->root . "/" . $file);
    }


}

