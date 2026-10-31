<?php

namespace TAS\Core;

// Collection of Static Function to valid the format of given Data.
class DataValidate
{
    /**
     * @description:  Valid if the phone number is $length digit valid number or not. Empty string will be treated as false.
     *
     * @param string $phone
     * @param int $length
     * @return bool
     */
    public static function ValidatePhoneFormat($phone, $length = 10)
    {
        if (null == $phone || empty($phone)) {
            return false;
        }

        $phone = str_replace([' ', '-', '(', ')', '.'], '', $phone);
        if ($length < 5 || $length > 12) {
            throw new \Exception('Phone length should be 5 to 12 at this moment');
        }
        if (strlen($phone) != $length) {
            return false;
        }
        if (!is_numeric($phone)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function ValidateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     * @return bool
     * @Note: True for  http://example.com OR http://www.example.com OR https://example.com OR https://www.example.com OR https://example
     * Only http and https: javascript://example.com/%0Aalert(1) is a "valid" URL to filter_var, and runs when shown as a link.
     */
    public static function ValidateURL($url)
    {
        if (null == $url) {
            return false;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Validate an IP Address
     * @param string $ip
     * @return bool
     */
    public static function ValidateIP($ip)
    {
        if (null == $ip) {
            return false;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }

    /**
     * Verifies if provided input is validate date or not.
     * False for anything that isn't one, an empty value or an array (date[]=x) included: it used to throw for those,
     * so a form value could end the request (Entity::InputValidate does not catch).
     * @param mixed $date
     * @return bool
     */
    public static function IsDate($date)
    {
        if (!is_string($date) || '' === trim($date) || !self::ContainDigits($date)) {
            return false;
        }

        try {
            $d = new \DateTime($date);

            return true;
        } catch (\Exception $err) {
            return false;
        }
    }

    /**
     * Checks if a string contains a number/digit in it.
     * @param string $str
     * @return bool
     */
    public static function ContainDigits(string $str)
    {
        for ($i = 0; $i < strlen($str); ++$i) {
            if (ctype_digit($str[$i])) {
                return true;
            }
        }

        return false;
    }

    /**
     * asc or desc, in any case. Anything else must never reach an "order by".
     *
     * @param mixed $direction
     * @return bool
     */
    public static function IsSortDirection($direction)
    {
        return is_string($direction) && in_array(strtolower($direction), ['asc', 'desc'], true);
    }

    /**
     * True when $column is a sort column the page itself offers, anything else would be SQL from the URL.
     * The offered ones are the default column, a key or 'sortstring' of $fields, and the first word of an $extra
     * entry like 'name asc'.
     *
     * @param mixed $column
     * @param string $default
     * @param array $fields column definitions keyed by column
     * @param array $extra
     * @return bool
     */
    public static function IsSortColumn($column, $default, array $fields, array $extra = [])
    {
        if (!is_string($column) || '' === $column) {
            return false;
        }
        if ($column === $default) {
            return true;
        }
        foreach ($fields as $field => $definition) {
            if ((string) $field === $column || (is_array($definition) && ($definition['sortstring'] ?? null) === $column)) {
                return true;
            }
        }
        foreach ($extra as $entry) {
            if (preg_split('/\s+/', trim((string) $entry))[0] === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate a password against a set of rules. Or throw an exception.
     * @param string $password
     * @return bool
     */
    public static function ValidatePassword(string $password): bool
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (strlen($password) < 8) {
            throw new \Exception('Password should be at least 8 characters in length.');
        }
        if (!$uppercase) {
            throw new \Exception('Password must include at least upper case letter.');
        }
        if (!$lowercase) {
            throw new \Exception('Password must include at least lower case letter.');
        }
        if (!$number) {
            throw new \Exception('Password must include at least one number.');
        }
        if (!$specialChars) {
            throw new \Exception('Password must include at least one special character.');
        }

        return true;
    }
}
