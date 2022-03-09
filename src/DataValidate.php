<?php

namespace TAS\Core;

// Collection of Static Function to valid the format of given Data.
class DataValidate
{
    /**
     * @description:  Valid if the phone number is $length digit valid number or not.
     *
     * @param mixed $phone
     * @param mixed $length
     */
    public static function ValidatePhoneFormat($phone, $length = 10)
    {
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

    public static function ValidateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    // @Note: True for  http://example.com OR http://www.example.com OR https://example.com OR https://www.example.com OR https://example
    public static function ValidateURL($url)
    {
        if (null == $url) {
            return false;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    // Validate an IP Address
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
     */
    public static function IsDate(string $date)
    {
        if (empty($date) || null == $date || !self::ContainDigits($date)) {
            throw new \InvalidArgumentException('Invalid argument, date must be any parsable date. preferrably in system time format.');
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
     * Validate a password against a set of rules. Or throw an exception.
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
