<?php
declare(strict_types=1);

namespace WpUtilService\Features;

enum RuntimeContextEnum: string
{
    case THEME = 'themes';
    case MUPLUGIN = 'mu-plugins';
    case PLUGIN = 'plugins';
}
