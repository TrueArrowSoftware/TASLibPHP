<?php
namespace TAS\Core;

/**
 * Handle all Template related parsing and management.
 *
 * @author TAS Team
 */
class TemplateHandler
{
    
    public static $TemplateName = [
        'single' => 'single.tpl',
        'home' => 'home.tpl',
        'admin' => 'admin.tpl',
        'login' => 'login.tpl',
        'popup' => 'popup.tpl'
    ];
    
    /**
     * Iterate and replace keywords in given Content.
     * It also replace AppConfig common variable.
     *
     * @param unknown $content
     * @param unknown $keywords
     */
    public static function PrepareContent(string $content, array $keywords)
    {
        if (is_array($keywords)) {
            reset($keywords);
            foreach ($keywords as $index => $value) {
                if (! is_array($value) && ! is_object($value)) {
                    $content = str_replace('{' . $index . '}', $value, $content);
                } else {
                    foreach ($value as $io => $vo) {
                        $content = str_replace('{' . $index . '-' . $io . '}', $vo, $content);
                    }
                }
            }
            reset($GLOBALS['AppConfig']);
            foreach ($GLOBALS['AppConfig'] as $index => $value) {
                if (! is_array($value) && ! is_object($value)) {
                    $content = str_replace('{' . $index . '}', $value, $content);
                }
            }
            
            return $content;
        } else {
            return $content;
        }
    }
    
    /**
     * Select template to use based on Template ID provided.
     *
     * @param string $pagetemplate
     */
    public static function TemplateChooser(string $pagetemplate)
    {
        if (empty(self::$TemplateName[$pagetemplate])) {
            throw new \Exception('Template '.$pagetemplate.' not available');
        }        
        return TemplateHandler::InsertTemplateContent($GLOBALS['AppConfig']['TemplatePath'] . DIRECTORY_SEPARATOR . self::$TemplateName[$pagetemplate], $GLOBALS['pageParse']);
    }
    
    /**
     * Insert Template Content.
     */
    public static function InsertTemplateContent($Templatefile, $content_keyword)
    {
        reset($GLOBALS['AppConfig']);
        if (file_exists($Templatefile)) {
            $filecontent = file($Templatefile);
            $filecontent = implode('', $filecontent);
            
            return TemplateHandler::PrepareContent($filecontent, $content_keyword);
        } else {
            return "Template file doesn't exists" . $Templatefile;
        }
    }
    
    /**
     * Using DOMNode, find inner HTML.
     *
     * @param DOMNode $element
     */
    public static function DOMInnerHTML(\DOMNode $element)
    {
        $innerHTML = '';
        $children = $element->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        
        return $innerHTML;
    }
    
    // Navigation with permission
    public static function GenerateNavigationMenu($arrNavigation = array(), $class = "", $returnLiOnly = false)
    {
        if (! is_array($arrNavigation))
            return null;
            $output = '';
            foreach ($arrNavigation as $v) {
                if (isset($GLOBALS['permission']->permissions[$GLOBALS['user']->UserRoleID][$v['permission_module']])) {
                    if (! $GLOBALS['permission']->CheckModulePermission(strtolower($v['permission_module']), $GLOBALS['user']->UserRoleID)) {
                        continue;
                    }
                } else {
                    continue;
                }
                
                $output3 = '';
                $isActive = '';
                $ShowParent = false;
                
                if (isset($v['child']) && count($v['child']) > 0) {
                    $output3 = '<div class="collapse" data-parent="#accordion" id="' . $v['anchor'] . '">
                                <ul class="submenu">';
                    foreach ($v['child'] as $v2) {
                        if (isset($GLOBALS['permission']->permissions[$GLOBALS['user']->UserRoleID][$v2['permission_module']])) {
                            if (! $GLOBALS['permission']->CheckModulePermission(strtolower($v2['permission_module']), $GLOBALS['user']->UserRoleID)) {
                                
                                continue;
                            }
                        } else {
                            continue;
                        }
                        
                        $isActive = '';
                        if ((parse_url($v2['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'])) {
                            $isActive = ' active';
                            $ShowParent = true;
                        }
                        
                        $output3 .= '<li class="' . $isActive . ' nav-item"><a class="d-flex text-white align-items-center" ' . (isset($v2['target']) && $v2['target'] == 'blank' ? 'target="blank"' : '') . ' href="' . $v2['link'] . '">' . ((isset($v2['icon']) && ! empty($v2['icon'])) ? '<i class="fas fa-' . $v2['icon'] . ' mr-2" aria-hidden="false"></i> ' : '') . _($v2['name']) . '</a></li>';
                    }
                    $output3 .= '</ul></div>';
                }
                if ($ShowParent || (parse_url($v['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'])) {
                    $isActive = ' active';
                }
                
                if (isset($v['type']) && $v['type'] == 'static') {
                    $output .= '<div class="card-header primary-bg-color ' . (parse_url($v['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'] ? 'no-dropdown active' : '') . '">
                                <a class="card-link text-white static-link" href="' . $v['link'] . '">
                                    ' . ((isset($v['icon']) && ! empty($v['icon'])) ? '<i class="fas fa-' . $v['icon'] . ' mr-2" aria-hidden="true"></i> ' : '') . _($v['name']) . '
                                </a>
                         </div>';
                } else {
                    $output .= '<div class="card-header primary-bg-color" id="' . $v['anchor'] . '1"><a data-toggle="collapse" aria-expanded="false" class="card-link text-white" href="#' . $v['anchor'] . '">' . ((isset($v['icon']) && ! empty($v['icon'])) ? '<i class="fas fa-' . $v['icon'] . ' mr-2" aria-hidden="true"></i> ' : '') . _($v['name']) . '</a></div>';
                }
                
                $output .= $output3;
            }
            return $output;
    }
    
    // Navigation without permission
    public static function GenerateNavigationMenuWithoutPermission($arrNavigation = array(), $class = "", $returnLiOnly = false)
    {
        if (! is_array($arrNavigation))
            return null;
            $output = "";
            
            foreach ($arrNavigation as $v) {
                $output3 = '';
                $isActive = '';
                $ShowParent = false;
                
                if (isset($v['child']) && count($v['child']) > 0) {
                    $output3 = '<div class="collapse" data-parent="#accordion" id="' . $v['anchor'] . '">
                                <ul class="submenu">';
                    foreach ($v['child'] as $v2) {
                        $isActive = '';
                        if ((parse_url($v2['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'])) {
                            $isActive = ' active';
                            $ShowParent = true;
                        }
                        
                        $output3 .= '<li class="' . $isActive . ' nav-item"><a class="d-flex text-white align-items-center" ' . (isset($v2['target']) && $v2['target'] == 'blank' ? 'target="blank"' : '') . ' href="' . $v2['link'] . '">' . ((isset($v2['icon']) && ! empty($v2['icon'])) ? '<i class="fas fa-' . $v2['icon'] . ' mr-2" aria-hidden="false"></i> ' : '') . _($v2['name']) . '</a></li>';
                    }
                    $output3 .= '</ul></div>';
                }
                if ($ShowParent || (parse_url($v['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'])) {
                    $isActive = ' active';
                }
                
                if (isset($v['type']) && $v['type'] == 'static') {
                    $output .= '<div class="card-header primary-bg-color ' . (parse_url($v['link'], PHP_URL_PATH) == $_SERVER['REQUEST_URI'] ? 'no-dropdown active' : '') . '">
                                <a class="card-link text-white static-link" href="' . $v['link'] . '">
                                    ' . ((isset($v['icon']) && ! empty($v['icon'])) ? '<i class="fas fa-' . $v['icon'] . ' mr-2" aria-hidden="true"></i> ' : '') . _($v['name']) . '
                                </a>
                         </div>';
                } else {
                    $output .= '<div class="card-header primary-bg-color" id="' . $v['anchor'] . '1"><a data-toggle="collapse" aria-expanded="false" class="card-link text-white" href="#' . $v['anchor'] . '">' . ((isset($v['icon']) && ! empty($v['icon'])) ? '<i class="fas fa-' . $v['icon'] . ' mr-2" aria-hidden="true"></i> ' : '') . _($v['name']) . '</a></div>';
                }
                
                $output .= $output3;
            }
            
            return $output;
    }
}