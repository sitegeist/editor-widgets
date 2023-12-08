<?php

defined('TYPO3') or die();

(static function () {
    if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
        $GLOBALS['TBE_STYLES']['skins']['dashboard']['stylesheetDirectories']['editor_widgets'] = 'EXT:editor_widgets/Resources/Public/Css/backend.css';
    }
})();
