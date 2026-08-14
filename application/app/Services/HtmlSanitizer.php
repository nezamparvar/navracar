<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

final class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,blockquote,ul,ol,li,h2,h3,h4,a[href|title|target|rel],code,pre');
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('URI.DisableExternalResources', true);
        $config->set('URI.DisableResources', true);
        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
