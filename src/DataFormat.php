<?php

namespace TAS\Core;

/**
 * Collection of Static Function to valid the format of given Data.
 */
class DataFormat
{
    /**
     * Clear the phone from given format such (111)-111-1111 to 1111111111. It remove space,- bracket and dot from phone only.
     *
     * @param [type] $phone
     */
    public static function CleanPhone(string $phone, int $length = 10)
    {
        if (empty(trim($phone))) {
            throw new \Exception('Invalid Arugment supplied');
        }
        if ($length < 5 && $length > 20) {
            throw new \Exception('Length of desire phone should be in between 5 to 20 characters only.');
        }

        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')', '.'], '', $phone);
        if (strlen($phone) > $length) {
            $phone = substr($phone, 0, $length);
        }

        return $phone;
    }

    /**
     * Format a give Phone as (123)-123-1234 or different one based on length of number given.
     *
     * @param string/int $phone
     * @param int $length Default to 10
     */
    public static function FormatPhone(string $phone, int $length = 10)
    {
        if (empty(trim($phone))) {
            return $phone;
        }

        $phone = DataFormat::CleanPhone($phone, $length);
        if (!DataValidate::ValidatePhoneFormat($phone, $length)) {
            throw new \Exception("Phone number {$phone} is not valid.");
        }

        $patterns = [
            7 => '/([0-9]{3})([0-9]{4})/',
            8 => '/([0-9]{4})([0-9]{4})/',
            9 => '/([0-9]{3})([0-9]{3})([0-9]{3})/',
            10 => '/([0-9]{3})([0-9]{3})([0-9]{4})/',
            11 => '/([0-9]{3})([0-9]{4})([0-9]{4})/',
            12 => '/([0-9]{4})([0-9]{4})([0-9]{4})/',
        ];

        $output = [
            7 => '$1-$2',
            8 => '$1-$2',
            9 => '$1-$2-$3',
            10 => '($1) $2-$3',
            11 => '($1) $2-$3',
            12 => '($1) $2-$3',
        ];

        return isset($patterns[$length]) ? preg_replace($patterns[$length], $output[$length], $phone) : $phone;
    }

    /**
     * UCWord formatting after lower a string.
     *
     * @param [type] $str
     */
    public static function FormatString(string $str)
    {
        return ucwords(strtolower($str));
    }

    /**
     * Validate password security.
     *
     * @param mixed $str
     */
    public static function ValidatePassword($str)
    {
        if (null == $str || empty($str)) {
            return false;
        }
        if (strlen($str) < 7) {
            return false;
        }
        if (preg_match('/^.*(?=.{6,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).*$/i', $str)) {
            return true;
        }

        return false;
    }

    /**
     * Convert Size in Bytes to respective Nice looking format.
     *
     * @param mixed $size
     * @param mixed $precision
     */
    public static function FormatBytes($size, $precision = 2)
    {
        $base = log($size) / log(1024);
        $suffixes = ['B', 'K', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
    }

    /**
     * Convert Database based value to presentable format.
     *
     * @param string $DBDate
     * @param mixed  $format
     */
    public static function DBToDateTimeFormat($DBDate, $format = '')
    {
        if (null == $format || empty($format)) {
            $format = Config::$DisplayDateTimeFormat;
        }
        if (null == $DBDate || empty($DBDate)) {
            return '';
        }
        if ('' != trim($DBDate)) {
            try {
                $d = new \DateTime($DBDate);

                return $d->format($format);
            } catch (\Exception $err) {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Convert Database DateTime object to Date Only. Look for DBToDateTimeFormat if you need Time as well.
     *
     * @param string $format = 'm/d/Y' Default return format
     * @param mixed  $DBDate
     */
    public static function DBToDateFormat($DBDate, $format = '')
    {
        if (null == $format || empty($format)) {
            $format = Config::$DisplayDateFormat;
        }
        if (null == $DBDate || empty($DBDate)) {
            return '';
        }
        if ('' != trim($DBDate)) {
            try {
                $d = new \DateTime($DBDate);

                return $d->format($format);
            } catch (\Exception $err) {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Return DB Formated Date from user input. Returns date as "Y-m-d H:i:s".
     *
     * @param string $date
     * @param mixed  $readformat
     */
    public static function DateToDBFormat($date, $readformat = 'm/d/Y H:i a')
    {
        if ('' == $date) {
            return '';
        }
        if (null == $readformat || empty($readformat)) {
            $readformat = Config::$DisplayDateTimeFormat;
        }

        try {
            $d = new \DateTime($date);

            return false !== $d ? $d->format(Config::$DateTimeFormatDB) : '';
        } catch (\Exception $err) {
            try {
                $d = \DateTime::createFromFormat($readformat, $date);

                return (false !== $d) ? $d->format(Config::$DateTimeFormatDB) : '';
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    public static function RemoveWhiteSpace($value)
    {
        if (null == $value || empty($value)) {
            return '';
        }
        $value = str_replace("\r", '', $value);
        $value = str_replace("\n", '', $value);

        return trim($value, " \t");
    }

    /**
     * Clearup the string for HTML and MySQL hacks.
     *
     * @param mixed $a_value
     *
     * @return mixed returns clean string or object/array as is
     */
    public static function DoSecure($a_value)
    {
        if (null == $a_value) {
            return '';
        }
        if (is_array($a_value)) {
            $output = $a_value;
        } elseif (is_object($a_value)) {
            $output = $a_value;
        } else {
            $output = trim($a_value);
            $output = str_replace('<!--', '', $output);
            // Replace JS Tag, HTML tags, etc...
            $search = [
                '@<script[^>]*?>.*?</script>@si',
                '@<[\/\!]*?[^<>]*?>@si',
                '@([\r\n])[\s]+@',
            ];
            $replace = [
                '',
                '',
                '\1',
            ];
            $output = preg_replace($search, $replace, $output);
            $output = htmlspecialchars($output);
        }

        return $output;
    }

    /**
     * Clean a string for Database Insert using default (mysql) database function.
     *
     * @param [type] $a_value
     */
    public static function DBString($a_value)
    {
        return @$GLOBALS['db']->Escape(DataFormat::DoSecure($a_value));
    }

    /**
     * Remove slashes added by web forms.
     *
     * @param [type] $a_value
     */
    public static function RemoveSlashes($a_value)
    {
        if (is_array($a_value)) {
            $output = [];
            foreach ($a_value as $key => $value) {
                if (is_array($value)) {
                    $output[$key] = $value;
                } else {
                    $output[$key] = stripslashes($value);
                }
            }
        } else {
            $output = stripslashes($a_value ?? '');
        }

        return $output;
    }

    /**
     * Process an array to be secure.
     */
    public static function DoSecureArray(array $a_value): array
    {
        if (is_array($a_value)) {
            foreach ($a_value as $index => $value) {
                if (!is_array($value)) {
                    $a_value[$index] = DataFormat::DoSecure($value);
                } else {
                    $a_value[$index] = DataFormat::DoSecureArray($value);
                }
            }
        }

        return $a_value;
    }

    /**
     * Humanize the output time.
     *
     * @return string humanized time
     */
    public static function HumanizeTime(int $timestamp, int $starttime = null)
    {
        if ($timestamp < 0 || empty($timestamp)) {
            throw new \InvalidArgumentException('Invalid argument, timestamp must be positive integer');
        }
        $starttime ??= time();
        $diff = $starttime - (int) $timestamp;

        if (0 == $diff) {
            return 'just now';
        }
        $intervals = [
            1 => [
                'year',
                31556926,
            ],
            $diff < 31556926 => [
                'month',
                2628000,
            ],
            $diff < 2629744 => [
                'week',
                604800,
            ],
            $diff < 604800 => [
                'day',
                86400,
            ],
            $diff < 86400 => [
                'hour',
                3600,
            ],
            $diff < 3600 => [
                'minute',
                60,
            ],
            $diff < 60 => [
                'second',
                1,
            ],
        ];
        $value = floor($diff / $intervals[1][1]);

        return $value.' '.$intervals[1][0].($value > 1 ? 's' : '').' ago';
    }

    /**
     * returns the randomly generated string.
     * Default it return 12 char long string, but can be change using Length parameter.
     *
     * @param int $length
     *                    Length of password to generate
     */
    public static function GenerateRandomPassword($length = 12)
    {
        $characters = '123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        do {
            $string = '';
            for ($p = 0; $p < $length; ++$p) {
                $x = (int) mt_rand(0, 54);
                $string .= $characters[$x];
            }
        } while (!DataFormat::ValidatePassword($string));

        return $string;
    }

    /**
     * Generates the Verification Code from Username.
     *
     * @param string $username
     *                         Expect a unique ID code to generate a Modified MD5 Hash
     *
     * @return string A Modified MD4 Hash of $username as string
     */
    public static function GenerateVerificationCode($username)
    {
        $verificationCode = $username;

        return md5($username << 2);
    }

    /**
     * Cleans junk characters from a given string.
     *
     * @param string $v the input string to be cleaned
     *
     * @return string the cleaned string with junk characters removed
     */
    public static function CleanJunkCharacters($v)
    {
        if (null == $v || empty($v)) {
            return '';
        }

        $output = trim($v);
        $search = str_split('ÃÂ¿½ï¿ï');
        $search2 = [
            '&Atilde;',
            '&macr;',
            '&frac12;',
            '&Acirc;',
            '&iquest;',
            '&iuml;',
        ];
        $searchfinal = array_merge($search, $search2);
        array_walk($searchfinal, function (&$v, $k) {
            $v = '/'.$v.'/i';
        });
        $replace = '';

        return preg_replace($searchfinal, $replace, $output);
    }

    /**
     * Returns a Numeric Get value after checking for security.
     * If $return is bool false, then it do nothing in case of failure but return false
     * else will redirect to give string url.
     *
     * @param string $var
     * @param mixed  $return
     */
    public static function ReturnNumericGet($var = 'id', $return = 'index.php')
    {
        if (!isset($_GET[$var]) || !is_numeric($_GET[$var]) || (int) $_GET[$var] <= 0) {
            if (is_bool($return) && false == $return) {
                return false;
            }
            Web::Redirect($return);
        } else {
            return (int) $_GET[$var];
        }
    }

    /**
     * Return the Age in year and month from given timstamp date.
     *
     * @param int $dob Age in timestamp
     *
     * @return string Age in formated year (or months)
     */
    public static function GetAge(int $dob): string
    {
        if ($dob < 0 || empty($dob)) {
            throw new \InvalidArgumentException('Invalid argument, dob (date of birth) must be positive integer, timestamp time.');
        }
        $dob = date('Y-m-d', $dob);
        $dobObject = new \DateTime($dob);
        $nowObject = new \DateTime();
        $diff = $dobObject->diff($nowObject);
        if ($diff->m > 0) {
            return $diff->y.' yrs '.$diff->m.' months';
        }

        return $diff->y.' yrs';
    }

    /**
     * Inverses a provided hex color.
     * If you pass a hex string with a
     * hash(#), the function will return a string with a hash prepended.
     *
     * @param string $color
     *                      Hex color to flip
     *
     * @return string Reversed hex color
     *
     * @author Koncept
     */
    public static function InverseHex($color)
    {
        if (null == $color || empty($color)) {
            return '';
        }
        $color = trim($color);
        $prependHash = false;

        if (false !== strpos($color, '#')) {
            $prependHash = true;
            $color = str_replace('#', '', $color);
        }
        $len = strlen($color);
        if (3 != $len && 6 != $len) {
            return '';
        }

        switch ($len) {
            case 3:
                $color = preg_replace('/(.)(.)(.)/', '\\1\\1\\2\\2\\3\\3', $color);

                break;

            case 6:
                break;

            default:
                trigger_error("Invalid hex ({$color}) length ({$len}) . Must be a minimum length of (3) or maxium of (6) characters", E_USER_ERROR);

                return '';
        }

        if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
            $color = htmlentities($color);
            trigger_error("Invalid hex string #{$color}", E_USER_ERROR);
        }

        $r = dechex(255 - hexdec(substr($color, 0, 2)));
        $r = (strlen($r) > 1) ? $r : '0'.$r;
        $g = dechex(255 - hexdec(substr($color, 2, 2)));
        $g = (strlen($g) > 1) ? $g : '0'.$g;
        $b = dechex(255 - hexdec(substr($color, 4, 2)));
        $b = (strlen($b) > 1) ? $b : '0'.$b;

        return ($prependHash ? '#' : null).$r.$g.$b;
    }

    /**
     * RGB to HTML Hex.
     *
     * @param unknown $red
     * @param unknown $green
     * @param unknown $blue
     *
     * @return string
     */
    public static function RGBToHex($red, $green, $blue)
    {
        return '#'.str_pad(dechex($red), '2', dechex($red)).str_pad(dechex($green), '2', dechex($green)).str_pad(dechex($blue), '2', dechex($blue));
    }

    /**
     * Return create slug.
     *
     * @param unknown $string
     */
    public static function CreateSlug($string)
    {
        if (null == $string || empty($string)) {
            return '';
        }
        $replace = '-';
        $string = strtolower($string);
        // replace / and . with white space
        $string = preg_replace('/[\\/\\.]/', ' ', $string);
        $string = preg_replace('/[^a-z0-9_\\s-]/', '', $string);
        // remove multiple dashes or whitespaces
        $string = preg_replace('/[\\s-]+/', ' ', $string);

        // convert whitespaces and underscore to $replace
        return preg_replace('/[\\s_]/', $replace, $string);
    }

    public static function RemoveNumberFormat(string $number)
    {
        return floatval(preg_replace('/[^\d.]/', '', $number));
    }
}
