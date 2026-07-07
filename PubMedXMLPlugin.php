<?php

/**
 * Main class for pubMed
 * 
 * @author Joe Simpson
 * 
 * @class PubMedXMLPlugin
 *
 * @brief PubMedXMLPlugin
 */

namespace APP\plugins\generic\pubMedXML;

use APP\core\Request;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class PubMedXMLPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            
            Hook::add('LoadHandler', [$this, 'setPageHandler']);

        }

        return $success;
    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
    {
        return __('plugins.generic.pubMedXML.displayName');
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
    {
        return __('plugins.generic.pubMedXML.description');
    }

    public function setPageHandler(string $hookName, array $args): bool
    {
        $page =& $args[0];
        $action =& $args[1];
        $handler =& $args[3];
        if ($this->getEnabled() && $page === 'article' && $action == 'viewXML') {
            $handler = new PubMedXMLPageHandler($this);
            return true;
        }
        return false;
    }

}
