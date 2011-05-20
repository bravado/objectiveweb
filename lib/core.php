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

global $_domains;

// Default db config
// TODO although this is somehow modular, there is NO SUPPORT for other databases yet
// THIS MUST BE REVIEWED WHEN ALL THE CORE FEATURES ARE WELL DEFINED
defined('DATABASE_PROVIDER') or define('DATABASE_PROVIDER', dirname(__FILE__) . '/drivers/mysql.php');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8');



require_once DATABASE_PROVIDER;

// Global system variables
$_domains = array();
$_subscriptions = array();


// CORE domains
register_domain('apps');

// TODO register dynamic domains (on the database)


// TODO directory é um módulo obrigatório (core depende de directory)
// content depende de directory
// mas desta forma, poderia existir um outro DIRECTORY_PROVIDER


function register_domain($domain_id, $params = array())
{
    global $_domains;

    $defaults = array(
        'schema' => array(),
        'driver' => 'MysqlDriver', // TODO review the mysql dependency for the core components (single config for all ?)
        'handler' => 'DefaultHandler'
    );

    // TODO validate schema
    $_domains[$domain_id] = array_merge($defaults, $params);
}


/**
 * @throws Exception
 * @param  $domain_id
 * @return MysqlDriver
 */
function get_domain($domain_id)
{
    global $_domains;

    if (empty($_domains[$domain_id])) {
        throw new Exception(_('Domain not found'), 404);
    }

    // TODO verificar permissões do domínio (execute/access)
    // TODO default permissão todomundo é ACCESS


    if (empty($_domains[$domain_id]['instance'])) {
        // TODO verificar se class existe
        $_domains[$domain_id]['instance'] = new $_domains[$domain_id]['driver']($domain_id, $_domains[$domain_id]);
    }

    return $_domains[$domain_id]['instance'];

}

/**
 * @brief Generates a Universally Unique IDentifier, version 4.
 *
 * @see http://tools.ietf.org/html/rfc4122#section-4.4
 * @see http://en.wikipedia.org/wiki/UUID
 * @return string A UUID, made up of 32 hex digits and 4 hyphens.
 */
function ow_oid()
{

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

function ow_subscribe($domain, $event, $callback)
{

    global $_subscriptions;

    // TODO verificar se callback já está in_array ?

    if (empty($_subscriptions["$domain:$event"])) {
        $_subscriptions["$domain:$event"] = array($callback);
    }
    else {
        $_subscriptions["$domain:$event"][] = $callback;
    }

}

function ow_trigger($domain, $event, $data)
{
    global $_subscriptions;

    foreach (@$_subscriptions["$domain:$event"] as $callback) {
        call_user_func($_subscriptions["$domain:$event"], $data);
    }

}

/**
 * from http://www.php.net/manual/en/function.uniqid.php#94959
 */
class UUID
{
    public static function v3($namespace, $name)
    {
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

    public static function v4()
    {
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

    public static function v5($namespace, $name)
    {
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


                       substr($hash, 0, 8),// 32 bits for "time_low"


                       substr($hash, 8, 4),// 16 bits for "time_mid"

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

    public static function is_valid($uuid)
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
                          '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }
}

// Internationalization fallback (no internationalization)
if (!function_exists('_')) {
    function _($string)
    {
        return $string;
    }
}