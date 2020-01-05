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
     * @param $id
     * @param $value
     * @param $name
     * @param $isrequired
     * @param $css
     * @param $size
     * @param $maxlength
     */
    public static function InputBox($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="text" id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'" '.$additionaattr.' />';
    }

    public static function InputDate($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="text" id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'" '.$additionaattr.' autocomplete="off"/>';
    }

    public static function InputEmail($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="email" id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'" '.$additionaattr.' />';
    }

    public static function InputPassword($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $size = 30, $maxlength = 50, $additionaattr = '')
    {
        return '<input type="password" id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'" '.$additionaattr.' />';
    }

    public static function InputCheckBox($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return ' <label class="custom-checkbox"><input type="checkbox" id="'.$id.'" name="'.($name == '' ? $id : $name).'"class="'.$css.($isrequired ? ' required' : '').
        '" '.(($value == 1 || $value == true) ? 'checked="checked" ' : '').$additionaattr.' />
                          <span class="checkmark-box"></span>';
    }

    public static function InputRadio($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return '
         <label class="custom-radio">
            <input type="radio" id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
            '" '.(($value == 1 || $value == true) ? 'checked="checked" ' : '').$additionaattr.' />
           <span class="checkmark"></span>';
    }

    public static function InputSelect($id, $options = '', $name = '', $isrequired = false, $css = 'form-control', $multiple = false, $size = 5, $additionaattr = '')
    {
        return '<select id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').'" '.
            ($multiple ? 'multiple="multiple" size="'.$size.'"' : '').' '.$additionaattr.' >'.$options.'</select>';
    }

    public static function InputText($id, $value = '', $name = '', $isrequired = false, $css = 'form-control', $rows = 30, $cols = 50, $additionaattr = '')
    {
        return '<textarea id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" rows="'.$rows.'" cols="'.$cols.'" '.$additionaattr.' >'.$value.'</textarea>';
    }

    public static function InputFile($id, $name = '', $isrequired = false, $css = 'form-control', $additionaattr = '')
    {
        return '<input id="'.$id.'" name="'.($name == '' ? $id : $name).'" class="'.$css.($isrequired ? ' required' : '').
        '" type="file" '.$additionaattr.' />';
    }

    public static function InputHidden($id, $value = '', $name = '', $additionaattr = '')
    {
        return '<input type="hidden" id="'.$id.'" name="'.($name == '' ? $id : $name).'" '.$additionaattr.' value="'.$value.'" />';
    }

    public static function InputWrapper($inputcode)
    {
        return '<div class="forminputwrapper">'.$inputcode.'</div>';
    }

    public static function Label($label, $for = '', $isrequired = false)
    {
        return '<label class="formlabel '.($isrequired ? ' requiredfield' : '').'" for="'.$for.'">'.$label.'</label>';
    }

    public static function FormField($label, $wrapper, $tag = '')
    {
        return '<div class="formfield '.$tag.'">'.$label.$wrapper.'<div class="clear"></div></div>';
    }

    public static function ReadOnly($value)
    {
        return '<div class="formreadonlyinput form-control">'.$value.'&nbsp;</div>';
    }

    public static function FormButton($html)
    {
        return '<div class="formbutton">'.$html.'</div>';
    }
}
