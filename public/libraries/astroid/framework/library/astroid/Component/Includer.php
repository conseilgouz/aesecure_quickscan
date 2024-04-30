<?php

/**
 * @package   Astroid Framework
 * @author    Astroid Framework Team https://astroidframe.work
 * @copyright Copyright (C) 2023 AstroidFrame.work.
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */

namespace Astroid\Component;

use Astroid\Framework;
use Astroid\Helper;

defined('_JEXEC') or die;

class Includer
{
    public static $params;
    public static function run($content = null)
    {
        if ($content === null) {
            $app = \JFactory::getApplication();
            $body = $app->getBody();
        } else {
            $body = $content;
        }
        $includers = [];
        $body = preg_replace_callback('/(<astroid:include\s[^>]*type=")([^"]*)("[^>]* \/>)/siU', function ($matches) use (&$includers) {
            $html = $matches[0];
            $method = Helper::classify($matches[2]);
            if (method_exists(self::class, '_' . $method)) {
                $includers[] = [
                    'name' => $matches[2],
                    'replace' => $matches[0],
                    'func' => '_' . $method
                ];
            }
            return $html;
        }, $body);

        $includers = array_reverse($includers);

        foreach ($includers as $includer) {
            $func           =   $includer['func'];
            $func_content   =   self::$func();
            if (!is_string($func_content)) {
                $func_content = '';
            }
            $body = ($includer['replace']) ? str_replace($includer['replace'], $func_content, $body) : $body;
        }

        if ($content === null) {
            $app->setBody($body);
        } else {
            return $body;
        }
    }

    public static function _headMeta()
    {
        $document = Framework::getDocument();
        return $document->renderMeta();
    }

    public static function _headStyles()
    {
        Helper\Head::styles();
        $document = Framework::getDocument();
        $content  = $document->renderLinks();
        $content .= $document->getStylesheets();
        $content .= $document->astroidInlineCSS();
        return $content;
    }

    public static function _headScripts()
    {
        $document = Framework::getDocument();
        $content = '';
        $content .= $document->getScripts('head');
        $content .= $document->getCustomTags('head');
        return $content;
    }

    public static function _bodyScripts()
    {
        $document = Framework::getDocument();
        if (Framework::isSite()) {
            $document->addScript('vendor/astroid/js/script.js', 'body');
        }
        if (Helper::getPluginParams()->get('astroid_debug', 0)) {
            $document->addScript('vendor/astroid/js/debug.js', 'body');
        }
        $content = '';
        $content .= $document->getScripts('body');
        $content .= $document->getCustomTags('body');
        if (Framework::isSite()) {
            $content .= '<script>jQuery.noConflict(true);</script>';
        }
        return $content;
    }

    public static function _debug()
    {
        return Helper::debug();
    }
}