<?php

namespace TAS\Core;

/**
 * Class to give HTML which help in consistent HTML for project.
 *
 * @author TAS Team
 */
class HTML
{
    /**
     * Encode a value for HTML text or an attribute. Entities already in it are kept, so a value escaped before
     * (e.g. stored through DataFormat::DoSecure) is not escaped twice.
     *
     * @param mixed $value
     */
    public static function Encode($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, null, false);
    }

    /**
     * Content that may carry intended HTML: encoded only when Config::$EscapeOutput is on and $html is false.
     *
     * @param mixed $value
     */
    public static function Content($value, bool $html = false): string
    {
        return (Config::$EscapeOutput && !$html) ? self::Encode($value) : (string) ($value ?? '');
    }

    /**
     * A URL for href/src: encoded, and '#' for a javascript:, vbscript: or data: URL.
     *
     * @param mixed $url
     */
    public static function Url($url): string
    {
        // the browser decodes entities and drops control characters/whitespace before it reads the scheme
        $plain = preg_replace('/[\x00-\x20]+/', '', html_entity_decode((string) ($url ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_match('/^(javascript|vbscript|data):/i', $plain) ? '#' : self::Encode($url);
    }

    /**
     * Create Input box.
     *
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param int $size
     * @param int $maxlength
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputBox($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="text" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" size="' . self::Encode($size) . '" maxlength="' . self::Encode($maxlength) . '" value="' . self::Encode($value) . '" ' . $additionaattr . ' />';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputColour($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return '<input type="text" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" value="' . self::Encode($value) . '" ' . $additionaattr . ' />';
    }

    /**
     * @param string $id
     * @param string|\DateTime $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param array|string $additionaattr
     * @return string
     */
    public static function InputDate($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = [])
    {
        $inputtime = new \TAS\Core\WebUI\InputDate();
        $inputtime->SetAttribute('ID', $id);
        $inputtime->SetAttribute('Name', empty($name) ? $id : $name);
        $inputtime->SetValue(($value instanceof \DateTime) ? $value->format('Y-m-d') : $value ?? '');
        $inputtime->SetAttribute('class', $css . ($isrequired ? ' required' : ''));
        if (is_array($additionaattr)) {
            foreach ($additionaattr as $key => $value) {
                $inputtime->SetAttribute($key, $value);
            }
        }

        return $inputtime->Render();
    }

    /**
     * @param string $id
     * @param string|\DateTime $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param array|string $additionaattr
     * @return string
     */
    public static function InputDateTime($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = [])
    {
        $inputtime = new \TAS\Core\WebUI\InputDateTime();
        $inputtime->SetAttribute('ID', $id);
        $inputtime->SetAttribute('Name', empty($name) ? $id : $name);
        $inputtime->SetValue(($value instanceof \DateTime) ? $value->format('Y-m-d H:i:s') : $value ?? '');
        $inputtime->SetAttribute('class', $css . ($isrequired ? ' required' : ''));
        if (is_array($additionaattr)) {
            foreach ($additionaattr as $key => $value) {
                $inputtime->SetAttribute($key, $value);
            }
        }

        return $inputtime->Render();
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param int $size
     * @param int $maxlength
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputEmail($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="email" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" size="' . self::Encode($size) . '" maxlength="' . self::Encode($maxlength) . '" value="' . self::Encode($value) . '" ' . $additionaattr . ' />';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param int $size
     * @param int $maxlength
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputPassword($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="password" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" size="' . self::Encode($size) . '" maxlength="' . self::Encode($maxlength) . '" value="' . self::Encode($value) . '" ' . $additionaattr . ' />';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputCheckBox($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return ' <label class="custom-checkbox"><input type="checkbox" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '"class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" ' . ((1 == $value || true == $value) ? 'checked="checked" ' : '') . $additionaattr . ' />
                          <span class="checkmark-box"></span>';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputRadio($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return '
         <label class="custom-radio">
            <input type="radio" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" ' . ((1 == $value || true == $value) ? 'checked="checked" ' : '') . $additionaattr . ' />
           <span class="checkmark"></span>';
    }

    /**
     * @param string $id
     * @param string $options
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param bool $multiple
     * @param int $size
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputSelect($id, $options = '', $name = '', $isrequired = false, $css = 'form-control', $multiple = false, $size = 5, $additionaattr = '')
    {
        return '<select id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) . '" ' .
            ($multiple ? 'multiple="multiple" size="' . self::Encode($size) . '"' : '') . ' ' . $additionaattr . ' >' . $options . '</select>';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param int $rows
     * @param int $cols
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputText($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $rows = 30, $cols = 50, $additionaattr = '')
    {
        return '<textarea id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" rows="' . self::Encode($rows) . '" cols="' . self::Encode($cols) . '" ' . $additionaattr . ' >' . self::Encode($value) . '</textarea>';
    }

    /**
     * @param string $id
     * @param string $name
     * @param bool $isrequired
     * @param string $css
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputFile($id, $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return '<input id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" class="' . self::Encode($css . ($isrequired ? ' required' : '')) .
            '" type="file" ' . $additionaattr . ' />';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param mixed $additionaattr
     * @return string
     */
    public static function InputHidden($id, $value = '', $name = '', $additionaattr = '')
    {
        return '<input type="hidden" id="' . self::Encode($id) . '" name="' . self::Encode('' == $name ? $id : $name) . '" ' . $additionaattr . ' value="' . self::Encode($value) . '" />';
    }

    /**
     * @param string $inputcode
     * @return string
     */
    public static function InputWrapper($inputcode)
    {
        return '<div class="forminputwrapper">' . $inputcode . '</div>';
    }

    /**
     * @param string $label
     * @param string $for
     * @param bool $isrequired
     * @return string
     */
    public static function Label($label, $for = '', $isrequired = false)
    {
        return '<label class="formlabel ' . ($isrequired ? ' requiredfield' : '') . '" for="' . self::Encode($for) . '">' . $label . '</label>';
    }

    /**
     * @param string $label
     * @param string $wrapper
     * @param string $tag
     * @return string
     */
    public static function FormField($label, $wrapper, $tag = '')
    {
        return '<div class="formfield ' . self::Encode($tag) . '">' . $label . $wrapper . '<div class="clear"></div></div>';
    }

    /**
     * @param string $value
     * @param string $id
     * @param string $css
     * @param bool $html value is intended HTML, never escaped (see Config::$EscapeOutput)
     * @return string
     */
    public static function ReadOnly($value, $id, $css, bool $html = false)
    {
        return '<div class="formreadonlyinput form-control ' . self::Encode($css) . '" id="' . self::Encode($id) . '">' . self::Content($value, $html) . '&nbsp;</div>';
    }

    /**
     * @param string $html
     * @return string
     */
    public static function FormButton($html)
    {
        return '<div class="formbutton">' . $html . '</div>';
    }
}
