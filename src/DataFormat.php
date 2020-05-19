<?php

namespace TAS\Core;

/*
 * Collection of Static Function to valid the format of given Data.
 */
class DataFormat
{
    public static function FormatPhone($phone, $length = 10)
    {
        if (trim($phone) == '') {
            return '';
        }
        $phone = trim($phone);
        $phone = str_replace(array(' ', '-', '(', ')', '.'), '', $phone);
        if (strlen($phone) > 12) {
            $phone = substr($phone, 0, 12);
        }

        if (!\TAS\Core\DataValidate::ValidatePhoneFormat($phone, $length)) {
            throw new \Exception("Phone number $phone is not valid.");
        }
        switch ($length) {
            case 5:
                return $phone;
                break;
            case 6:
                return $phone;
                break;
            case 7:
                return preg_replace('/([0-9]{3})([0-9]{4})/', '$1-$2', $phone);
                break;
            case 8:
                return preg_replace('/([0-9]{4})([0-9]{4})/', '$1-$2', $phone);
                break;
            case 9:
                return preg_replace('/([0-9]{3})([0-9]{3})([0-9]{3})/', '$1-$2-$3', $phone);
                break;
            case 10:
                return preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2-$3', $phone);
                break;
            case 11:
                return preg_replace('/([0-9]{3})([0-9]{4})([0-9]{4})/', '($1) $2-$3', $phone);
                break;
            case 12:
                return preg_replace('/([0-9]{4})([0-9]{4})([0-9]{4})/', '($1) $2-$3', $phone);
                break;
            default:
                return $phone;
                break;
        }
    }

    public static function FormatString($str)
    {
        return ucwords(strtolower($str));
    }

    /**
     * Validate password security.
     */
    public static function ValidatePassword($str)
    {
        if (strlen($str) < 7) {
            return false;
        } elseif (preg_match('/^.*(?=.{6,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).*$/i', $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Convert Size in Bytes to respective Nice looking format.
     */
    public static function FormatBytes($size, $precision = 2)
    {
        $base = log($size) / log(1024);
        $suffixes = array('B', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
    }

    /**
     * Convert Database based value to presentable format.
     *
     * @param string $DBDate
     */
    public static function DBToDateTimeFormat($DBDate, $format = 'm/d/Y H:i a')
    {
        if (trim($DBDate) != '') {
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
     * @param string $DBDatedate
     * @param string $format     = 'm/d/Y' Default return format
     */
    public static function DBToDateFormat($DBDate, $format = 'm/d/Y')
    {
        if (trim($DBDate) != '') {
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
     * @param string $format = 'm/d/Y H:i:a' Read Format
     */
    public static function DateToDBFormat($date, $format = 'm/d/Y H:i a')
    {
        if ($date == '') {
            return '';
        }
        try {
            $d = new \DateTime($date);

            return $d !== false ? $d->format('Y-m-d H:i:s') : '';
        } catch (\Exception $err) {
            try {
                $d = \DateTime::createFromFormat($format, $date);

                return ($d !== false) ? $d->format('Y-m-d H:i:s') : '';
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    public static function RemoveWhiteSpace($value)
    {
        $value = str_replace("\r", '', $value);
        $value = str_replace("\n", '', $value);
        $value = trim($value, " \t");

        return $value;
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
        if (is_array($a_value)) {
            $output = $a_value;
        } elseif (is_object($a_value)) {
            $output = $a_value;
        } else {
            $output = trim($a_value);
            $output = str_replace('<!--', '', $output);
            // Replace JS Tag, HTML tags, etc...
            $search = array(
                '@<script[^>]*?>.*?</script>@si',
                '@<[\/\!]*?[^<>]*?>@si',
                '@([\r\n])[\s]+@',
            );
            $replace = array(
                '',
                '',
                '\1',
            );
            $output = preg_replace($search, $replace, $output);
            $output = htmlspecialchars($output);
        }

        return $output;
    }

    /**
     * Clean a string for Database Insert using default (mysql) database function.
     *
     * @param [type] $a_value
     *
     * @return void
     */
    public static function DBString($a_value)
    {
        return @$GLOBALS['db']->Escape(DataFormat::DoSecure($a_value));
    }

    /**
     * Remove slashes added by web forms.
     *
     * @param [type] $a_value
     *
     * @return void
     */
    public static function RemoveSlashes($a_value)
    {
        if (is_array($a_value)) {
            $output = array();
            foreach ($a_value as $key => $value) {
                if (is_array($value)) {
                    $output[$key] = $value;
                } else {
                    $output[$key] = stripslashes($value);
                }
            }
        } else {
            $output = stripslashes($a_value);
        }

        return $output;
    }

    /**
     * Process an array to be secure.
     *
     * @param array $a_value
     *
     * @return array
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
     * @param int $timestamp
     *
     * @return string humanized time
     */
    public static function HumanizeTime(int $timestamp)
    {
        if ($timestamp < 0 || empty($timestamp)) {
            throw new \InvalidArgumentException('Invalid argument, timestamp must be positive integer');
        }
        $diff = time() - (int) $timestamp;

        if ($diff == 0) {
            return 'just now';
        }
        $intervals = array(
            1 => array(
                'year',
                31556926,
            ),
            $diff < 31556926 => array(
                'month',
                2628000,
            ),
            $diff < 2629744 => array(
                'week',
                604800,
            ),
            $diff < 604800 => array(
                'day',
                86400,
            ),
            $diff < 86400 => array(
                'hour',
                3600,
            ),
            $diff < 3600 => array(
                'minute',
                60,
            ),
            $diff < 60 => array(
                'second',
                1,
            ),
        );
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
        } while (!\TAS\Core\DataFormat::ValidatePassword($string));

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
        $verificationCode = md5($username << 2);

        return $verificationCode;
    }

    /**
     * Clean the given string $v from junk characters.
     *
     * @param unknown_type $v
     */
    public static function CleanJunkCharacters($v)
    {
        $output = trim($v);
        $search = str_split('ÃÂ¿½ï¿ï');
        $search2 = array(
            '&Atilde;',
            '&macr;',
            '&frac12;',
            '&Acirc;',
            '&iquest;',
            '&iuml;',
        );
        $search = array_merge($search, $search2);
        array_walk($search, function (&$v, $k) {
            $v = '/'.$v.'/i';
        });
        $replace = '';
        // echo $output;
        $output = preg_replace($search, $replace, $output);

        return $output;
    }

    /**
     * Returns a Numeric Get value after checking for security.
     * If $return is bool false, then it do nothing in case of failure but return false
     * else will redirect to give string url.
     *
     * @param string $var
     * @param mixed  $return.
     *                        either bool false or string url.
     */
    public static function ReturnNumericGet($var = 'id', $return = 'index.php')
    {
        if (!isset($_GET[$var]) || !is_numeric($_GET[$var]) || (int) $_GET[$var] <= 0) {
            if (is_bool($return) && $return == false) {
                return false;
            } else {
                \TAS\Core\Web::Redirect($return);
            }
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
        } else {
            return $diff->y.' yrs';
        }
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
     *
     *         Last Update: 2008-10-05
     */
    public static function InverseHex($color)
    {
        $color = trim($color);
        $prependHash = false;

        if (strpos($color, '#') !== false) {
            $prependHash = true;
            $color = str_replace('#', null, $color);
        }

        switch ($len = strlen($color)) {
            case 3:
                $color = preg_replace('/(.)(.)(.)/', '\\1\\1\\2\\2\\3\\3', $color);
                break;
            case 6:
                break;
            default:
                // trigger_error("Invalid hex length ($len). Must be a minimum length of (3) or maxium of (6) characters", E_USER_ERROR);
                return '';
        }

        if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
            $color = htmlentities($color);
            trigger_error("Invalid hex string #$color", E_USER_ERROR);
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
     * @param string  $ext
     */
    public static function CreateSlug($string)
    {
        $replace = '-';
        $string = strtolower($string);
        // replace / and . with white space
        $string = preg_replace("/[\/\.]/", ' ', $string);
        $string = preg_replace("/[^a-z0-9_\s-]/", '', $string);
        // remove multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", ' ', $string);
        // convert whitespaces and underscore to $replace
        $string = preg_replace("/[\s_]/", $replace, $string);

        return $string;
    }

    public static function RemoveNumberFormat(string $number)
    {
        return floatval(preg_replace('/[^\d.]/', '', $number));
    }
}
