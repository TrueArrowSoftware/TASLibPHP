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
        return '<input type="text" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
            '" size="' . $size . '" maxlength="' . $maxlength . '" value="' . $value . '" ' . $additionaattr . ' />';
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
        return '<input type="text" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
            '" value="' . $value . '" ' . $additionaattr . ' />';
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
        return '<input type="email" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
            '" size="' . $size . '" maxlength="' . $maxlength . '" value="' . $value . '" ' . $additionaattr . ' />';
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
        return '<input type="password" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
            '" size="' . $size . '" maxlength="' . $maxlength . '" value="' . $value . '" ' . $additionaattr . ' />';
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
        return ' <label class="custom-checkbox"><input type="checkbox" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '"class="' . $css . ($isrequired ? ' required' : '') .
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
            <input type="radio" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
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
        return '<select id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') . '" ' .
            ($multiple ? 'multiple="multiple" size="' . $size . '"' : '') . ' ' . $additionaattr . ' >' . $options . '</select>';
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
        return '<textarea id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
            '" rows="' . $rows . '" cols="' . $cols . '" ' . $additionaattr . ' >' . $value . '</textarea>';
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
        return '<input id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" class="' . $css . ($isrequired ? ' required' : '') .
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
        return '<input type="hidden" id="' . $id . '" name="' . ('' == $name ? $id : $name) . '" ' . $additionaattr . ' value="' . $value . '" />';
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
        return '<label class="formlabel ' . ($isrequired ? ' requiredfield' : '') . '" for="' . $for . '">' . $label . '</label>';
    }

    /**
     * @param string $label
     * @param string $wrapper
     * @param string $tag
     * @return string
     */
    public static function FormField($label, $wrapper, $tag = '')
    {
        return '<div class="formfield ' . $tag . '">' . $label . $wrapper . '<div class="clear"></div></div>';
    }

    /**
     * @param string $value
     * @param string $id
     * @param string $css
     * @return string
     */
    public static function ReadOnly($value, $id, $css)
    {
        return '<div class="formreadonlyinput form-control ' . $css . '" id="' . $id . '">' . $value . '&nbsp;</div>';
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
