<?php

namespace Concrete\Package\CommunityStoreAffirm\Affirm\Util;

use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\Page\Page;

final class LanguagePath
{
    public static function getCurrent(): string
    {
        $page = Page::getCurrentPage();
        if (!$page) {
            return '';
        }

        $section = Section::getBySectionOfSite($page);
        if ($section !== null) {
            return $section->getCollectionHandle();
        }

        return '';
    }
}
